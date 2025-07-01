<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StoreController;



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

Route::get('public/stores', [StoreController::class, 'publicIndex']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
});

Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    // Store resource endpoints
    Route::get('stores', [StoreController::class, 'index'])->name('candidate.stores.index');
    Route::post('stores', [StoreController::class, 'store'])->name('candidate.stores.store');
    Route::get('stores/{id}', [StoreController::class, 'show'])->name('candidate.stores.show');
    Route::post('stores/{id}', [StoreController::class, 'update'])->name('candidate.stores.update.partial');
    Route::delete('stores/{id}', [StoreController::class, 'destroy'])->name('candidate.stores.destroy');
});


Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    // Store resource endpoints
    Route::get('create-admin', [StoreController::class, 'registerAdmin'])->name('create.admin');
    
});