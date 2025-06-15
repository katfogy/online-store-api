<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Traits\HasJsonResponse;
use App\Traits\GeneratesAuthAccessCredentials;
use App\Support\HttpConstants as HTTP;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ChangePasswordRequest;

use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    use HasJsonResponse, GeneratesAuthAccessCredentials;
    
   
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        return $this->jsonResponse(HTTP::HTTP_CREATED, 'Registration successful. Please verify your email.', [
            'user' => $user,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->jsonResponse(HTTP::HTTP_UNAUTHENTICATED, 'Invalid credentials');
        }

        if (! $user->hasVerifiedEmail()) {
            return $this->jsonResponse(HTTP::HTTP_FORBIDDEN, 'Please verify your email before logging in.');
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


    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {

            return $this->jsonResponse(HTTP::HTTP_FORBIDDEN, 'Invalid verification link');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Email already verified');
        }

        $user->markEmailAsVerified();
        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Email verified successfully');

    }

}
