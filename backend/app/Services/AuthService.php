<?php

namespace App\Services;

use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'] ?? 'VIEWER',
        ]);

        return $user;
    }

    public function login(string $email, string $password, array $deviceInfo = []): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        if (!$user->is_active) {
            throw new \Exception('Account is inactive');
        }

        // Generate tokens
        $accessToken = JWTAuth::fromUser($user);
        $refreshToken = $this->generateRefreshToken($user, $deviceInfo);

        return [
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken->token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    public function refresh(string $refreshTokenString): array
    {
        $refreshToken = RefreshToken::where('token', $refreshTokenString)->first();

        if (!$refreshToken) {
            throw new \Exception('Invalid refresh token');
        }

        $user = $refreshToken->user;

        if (!$refreshToken->isValid($user->token_version)) {
            throw new \Exception('Token has been revoked or expired');
        }

        // Generate new tokens
        $accessToken = JWTAuth::fromUser($user);
        $newRefreshToken = $this->generateRefreshToken($user, [
            'ip_address' => $refreshToken->ip_address,
            'user_agent' => $refreshToken->user_agent,
        ]);

        // Delete old refresh token
        $refreshToken->delete();

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken->token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    public function logout(User $user, ?string $refreshTokenString = null): void
    {
        if ($refreshTokenString) {
            RefreshToken::where('user_id', $user->id)
                ->where('token', $refreshTokenString)
                ->delete();
        }

        JWTAuth::invalidate(JWTAuth::getToken());
    }

    public function logoutAll(User $user): void
    {
        // Increment token version to invalidate all tokens
        $user->incrementTokenVersion();

        // Delete all refresh tokens
        RefreshToken::where('user_id', $user->id)->delete();
    }

    private function generateRefreshToken(User $user, array $deviceInfo = []): RefreshToken
    {
        $token = Str::random(128);
        $expiresAt = Carbon::now()->addMinutes(config('jwt.refresh_ttl', 43200));

        return RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $token),
            'token_version' => $user->token_version,
            'expires_at' => $expiresAt,
            'ip_address' => $deviceInfo['ip_address'] ?? null,
            'user_agent' => $deviceInfo['user_agent'] ?? null,
        ]);
    }

    public function getProfile(User $user): User
    {
        return $user->load('refreshTokens');
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }
}