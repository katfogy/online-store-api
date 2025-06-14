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


class AuthController extends Controller
{
    use HasJsonResponse, GeneratesAuthAccessCredentials;
    
    public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6|confirmed',
        'phone_number' => 'nullable|string|max:20',
    ]);

    if ($validator->fails()) {
        return $this->jsonResponse(HTTP::HTTP_VALIDATION_ERROR, 'Validation failed', $validator->errors());
    }

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone_number' => $request->phone_number,
        'password' => Hash::make($request->password),
    ]);

    // Send email verification
    event(new Registered($user));

    [$token, $expiresAt] = $this->generateAccessCredentialsFor($user);

    return $this->jsonResponse(HTTP::HTTP_CREATED, 'Registration successful. Please verify your email.', [
        'token' => $token,
        'expires_at' => $expiresAt,
        'user' => $user,
    ]);
}

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return $this->jsonResponse(HTTP::HTTP_VALIDATION_ERROR, 'Validation failed', $validator->errors());
        }
    
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


    public function logout(Request $request)
{
    $user = $request->user();
    $user->currentAccessToken()->delete();

    return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Logout successful');
}

public function changePassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'current_password' => 'required|string',
        'new_password' => 'required|string|min:6|confirmed', // requires new_password_confirmation
    ]);

    if ($validator->fails()) {
        return $this->jsonResponse(HTTP::HTTP_VALIDATION_ERROR, 'Validation failed', $validator->errors());
    }

    $user = $request->user();

    // Check current password
    if (!Hash::check($request->current_password, $user->password)) {
        return $this->jsonResponse(HTTP::HTTP_FORBIDDEN, 'Current password is incorrect');
    }

    // Update password
    $user->update([
        'password' => Hash::make($request->new_password),
    ]);

    return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Password changed successfully');
}

}
