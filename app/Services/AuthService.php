<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\OtpMail;
use App\Traits\GeneratesAuthAccessCredentials;
use App\Models\Role;

class AuthService
{
    use GeneratesAuthAccessCredentials;

    public function registerUser(array $data): User
    {
        return DB::transaction(function () use ($data): User {
              // Check if role_id is provided, else fallback to 'user'
        $role = isset($data['role_id'])
        ? Role::where('id', $data['role_id'])->first()
        : Role::where('name', 'User')->first();

    if (!$role) {
        throw new \Exception('Role not found.');
    }

        $user = User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'phone_number'=> $data['phone_number'],
            'password'    => Hash::make($data['password']),
            'role_id'     => $role->id, 
        ]);

            $this->sendOtp($user, 'account_creation');
            return $user;
        });
    }

    public function loginUser(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return ['error' => 'Invalid credentials', 'status' => 401];
        }

        if (! $user->email_verified_at) {
            return ['error' => 'Please verify your email with OTP before logging in.', 'status' => 403];
        }

        [$token, $expiresAt] = $this->generateAccessCredentialsFor($user);

        return [
            'user' => $user,
            'token' => $token,
            'expires_at' => $expiresAt,
            'status' => 200,
            'success' => 'Login successful',
        ];
    }

    public function verifyAccountOtp(string $email, string $otpCode): array
    {
        return DB::transaction(function () use ($email, $otpCode): array {
            $user = User::where('email', $email)->first();

            if (! $user) {
                return ['error' => 'User not found', 'status' => 404];
            }

            $otp = Otp::where('user_id', $user->id)
                ->where('type', 'account_creation')
                ->where('otp', $otpCode)
                ->where('expired_at', '>', now())
                ->latest()
                ->first();

            if (! $otp) {
                $this->sendOtp($user, 'account_creation');
                return ['error' => 'Invalid or expired OTP. A new OTP has been sent.', 'status' => 403];
            }

            $user->update(['email_verified_at' => now()]);
            $otp->delete();

            return ['success' => 'Email verified successfully', 'status' => 200];
        });
    }

    public function verifyPasswordOtp(string $email, string $otpCode, string $newPassword): array
    {
        return DB::transaction(function () use ($email, $otpCode, $newPassword): array {
            $user = User::where('email', $email)->first();

            if (! $user) {
                return ['error' => 'User not found', 'status' => 404];
            }

            $otp = Otp::where('user_id', $user->id)
                ->where('type', 'change_password')
                ->where('otp', $otpCode)
                ->where('expired_at', '>', now())
                ->latest()
                ->first();

            if (! $otp) {
                return ['error' => 'Invalid or expired OTP.', 'status' => 403];
            }

            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            $otp->delete();
            return ['success' => 'Password changed successfully.', 'status' => 200];
        });
    }

    public function resendOtp(User $user, string $type): void
    {
        $this->sendOtp($user, $type);
    }

    private function sendOtp(User $user, string $type): void
    {
        $otpCode = rand(100000, 999999);

        Otp::updateOrCreate(
            ['user_id' => $user->id, 'type' => $type],
            [
                'otp' => $otpCode,
                'expired_at' => now()->addMinutes(10),
            ]
        );

        Mail::to($user->email)->queue(new OtpMail($otpCode, $type));
    }
}
