<?php

namespace App\Traits;

use App\Models\User;
use Carbon\Carbon;

trait GeneratesAuthAccessCredentials
{
    private function generateAccessCredentialsFor(User $user, ?array $abilities = []): array
    {
        $user->tokens()->delete();

        $token = $user->createToken($user->email, $abilities);
        $expiresAt = Carbon::now()->addMinutes(config('sanctum.expiration'))->getTimestamp();
        $user->withAccessToken($token->accessToken);

        return [$token->plainTextToken, $expiresAt];
    }
}
