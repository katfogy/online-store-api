<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Models\User;


// Testing
Route::get('/ping', function () {
    return response()->json(['message' => 'API is working']);
});

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/request-password-otp', [AuthController::class, 'requestChangePasswordOtp']);
Route::post('/verify-password-otp', [AuthController::class, 'verifyChangePasswordOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
// No auth:sanctum

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

