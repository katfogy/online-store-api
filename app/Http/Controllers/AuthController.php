<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\HasJsonResponse;
use App\Support\HttpConstants as HTTP;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Requests\VerifyPasswordOtpRequest;
use App\Services\AuthService;

class AuthController extends Controller
{
    use HasJsonResponse;

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->registerUser($request->validated());

            return $this->jsonResponse(HTTP::HTTP_CREATED, 'Registration successful. Check your email for the OTP.', [
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(HTTP::HTTP_SERVER_ERROR, 'Registration failed. Please try again.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function registerAdmin(RegisterRequest $request)
    {
        try {
            $user = $this->authService->registerUser($request->validated());

            return $this->jsonResponse(HTTP::HTTP_CREATED, 'Registration successful. Check your email for the OTP.', [
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse(HTTP::HTTP_SERVER_ERROR, 'Registration failed. Please try again.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function login(LoginRequest $request)
    {
        $response = $this->authService->loginUser($request->email, $request->password);

        return isset($response['error'])
            ? $this->jsonResponse($response['status'], $response['error'])
            : $this->jsonResponse(HTTP::HTTP_CREATED, 'Login successful', [
                'user' => $response['user'],
                'token' => $response['token'],
                'expires_at' => $response['expires_at'],
            ]);
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();
        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Logout successful');
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        $response = $this->authService->verifyAccountOtp($request->email, $request->otp);

        return isset($response['error'])
            ? $this->jsonResponse($response['status'], $response['error'])
            : $this->jsonResponse($response['status'], $response['success']);
    }

    public function verifyChangePasswordOtp(VerifyPasswordOtpRequest $request)
    {
        $response = $this->authService->verifyPasswordOtp(
            $request->email,
            $request->otp,
            $request->new_password
        );

        return isset($response['error'])
            ? $this->jsonResponse($response['status'], $response['error'])
            : $this->jsonResponse($response['status'], $response['success']);
    }

    public function requestChangePasswordOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        $this->authService->resendOtp($user, 'change_password');

        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'OTP sent to your email.');
    }

    public function resendOtp(ResendOtpRequest $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (! $user) {
                return $this->jsonResponse(HTTP::HTTP_NOT_FOUND, 'User not found.');
            }

            $this->authService->resendOtp($user, $request->type);

            return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'OTP resent successfully.');
        } catch (\Exception $e) {
            return $this->jsonResponse(HTTP::HTTP_SERVER_ERROR, 'Failed to resend OTP.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
