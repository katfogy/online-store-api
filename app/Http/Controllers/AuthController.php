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
use App\Http\Requests\VerifyPasswordOtpRequest;


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

            return $this->jsonResponse(HTTP::HTTP_CREATED, 'Registration successful. Check your email for the OTP.', [
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->jsonResponse(HTTP::HTTP_SERVER_ERROR, 'Registration failed. Please try again.', [
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



    public function requestChangePasswordOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();

        $this->sendOtp($user, 'change_password');

        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'OTP sent to your email.');
    }

    /**
     * Verify OTP and change password
     */
    public function verifyChangePasswordOtp(VerifyPasswordOtpRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = User::where('email', $request->email)->first();

            $otp = Otp::where('user_id', $user->id)
                ->where('type', 'change_password')
                ->where('otp', $request->otp)
                ->where('expired_at', '>', now())
                ->latest()
                ->first();

            if (! $otp) {
                return $this->jsonResponse(HTTP::HTTP_FORBIDDEN, 'Invalid or expired OTP.');
            }

            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            $otp->delete();
            DB::commit();

            return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Password changed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->jsonResponse(HTTP::HTTP_SERVER_ERROR, 'Password change failed.', [
                'error' => $e->getMessage(),
            ]);
        }
    }



    public function resendOtp(ResendOtpRequest $request)
{
    try {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->jsonResponse(HTTP::HTTP_NOT_FOUND, 'User not found.');
        }

        $this->sendOtp($user, $request->type);

        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'OTP resent successfully.');
    } catch (\Exception $e) {
        return $this->jsonResponse(HTTP::HTTP_SERVER_ERROR, 'Failed to resend OTP.', [
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

        // Queue the email using Markdown mail
        Mail::to($user->email)->queue(new OtpMail($otpCode, $type));
    }
}


