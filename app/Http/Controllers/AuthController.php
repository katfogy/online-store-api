<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Traits\HasJsonResponse;
use App\Traits\GeneratesAuthAccessCredentials;
use App\Support\HttpConstants as HTTP;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Otp;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Mail\OtpMail;


use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    use HasJsonResponse, GeneratesAuthAccessCredentials;

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'password' => Hash::make($request->password),
            ]);

            $this->sendOtp($user, 'account_creation');

            DB::commit();

            return $this->jsonResponse(HTTP::HTTP_CREATED, 'Registration successful. Please check your email for the OTP.', [
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->jsonResponse(HTTP::HTTP_SERVER_ERROR, 'Registration failed. Please try again.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        DB::beginTransaction();
    
        try {
            $user = User::where('email', $request->email)->first();
    
            if (! $user) {
                return $this->jsonResponse(HTTP::HTTP_NOT_FOUND, 'User not found');
            }
    
            $otp = Otp::where('user_id', $user->id)
                      ->where('type', 'account_creation')
                      ->where('otp', $request->otp)
                      ->where('expired_at', '>', now())
                      ->latest()
                      ->first();
    
            if (! $otp) {
                $this->sendOtp($user, 'account_creation');
                return $this->jsonResponse(HTTP::HTTP_FORBIDDEN, 'Invalid or expired OTP. A new OTP has been sent.');
            }
    
            $user->email_verified_at = now();
            $user->save();
            $otp->delete();
    
            DB::commit();
    
            return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Email verified successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->jsonResponse(HTTP::HTTP_SERVER_ERROR, 'OTP verification failed.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->jsonResponse(HTTP::HTTP_UNAUTHENTICATED, 'Invalid credentials');
        }

        if (! $user->email_verified_at) {
            return $this->jsonResponse(HTTP::HTTP_FORBIDDEN, 'Please verify your email with OTP before logging in.');
        }

        [$token, $expiresAt] = $this->generateAccessCredentialsFor($user);

        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Login successful', [
            'token' => $token,
            'expires_at' => $expiresAt,
            'user' => $user,
        ]);
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();
        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Logout successful');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->jsonResponse(HTTP::HTTP_FORBIDDEN, 'Current password is incorrect');
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Password changed successfully');
    }



public function resendOtp(ResendOtpRequest $request)
{
    $user = User::where('email', $request->email)->first();

    if (! $user) {
        return $this->jsonResponse(HTTP::HTTP_NOT_FOUND, 'User not found');
    }

    try {
        $this->sendOtp($user, 'account_creation');
        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'A new OTP has been sent to your email.');
    } catch (\Exception $e) {
        return $this->jsonResponse(HTTP::HTTP_SERVER_ERROR, 'Failed to resend OTP', [
            'error' => $e->getMessage(),
        ]);
    }
}



    private function sendOtp(User $user, string $type)
    {
        $otpCode = rand(100000, 999999);

        Otp::updateOrCreate(
            ['user_id' => $user->id, 'type' => $type],
            [
                'otp' => $otpCode,
                'expired_at' => now()->addMinutes(10),
            ]
        );

        Mail::to($user->email)->queue(new OtpMail($otpCode));
    }



}
