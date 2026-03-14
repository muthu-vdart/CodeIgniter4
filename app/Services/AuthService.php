<?php

namespace App\Services;

use App\Models\RefreshTokenModel;
use App\Models\UserModel;
use CodeIgniter\I18n\Time;

class AuthService
{
    public function __construct(
        private readonly UserModel $userModel = new UserModel(),
        private readonly RefreshTokenModel $refreshTokenModel = new RefreshTokenModel(),
        private readonly JwtService $jwtService = new JwtService()
    ) {
    }

    public function login(string $email, string $password): ?array
    {
        $user = $this->userModel->findByEmail($email);
        if (! $user || ! password_verify($password, $user['password_hash'])) {
            return null;
        }

        if ((int) $user['is_active'] !== 1) {
            return null;
        }

        $profile = $this->userModel->getUserProfile((int) $user['id']);
        $config = config('TaskManagement');

        $accessToken = $this->jwtService->createToken([
            'sub' => $profile['id'],
            'email' => $profile['email'],
            'role' => $profile['role_slug'],
            'permissions' => $profile['permissions'],
        ], (int) (getenv('TASK_JWT_TTL') ?: $config->jwtTtlSeconds));

        $refreshTokenPlain = bin2hex(random_bytes(48));
        $this->refreshTokenModel->insert([
            'user_id' => $profile['id'],
            'token_hash' => hash('sha256', $refreshTokenPlain),
            'expires_at' => Time::now()->addSeconds((int) (getenv('TASK_REFRESH_TTL') ?: $config->refreshTtlSeconds))->toDateTimeString(),
            'created_at' => Time::now()->toDateTimeString(),
        ]);

        $this->userModel->update($profile['id'], ['last_login_at' => Time::now()->toDateTimeString()]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenPlain,
            'user_id' => $profile['id'],
            'role' => $profile['role_name'],
            'permissions' => $profile['permissions'],
            'user' => $profile,
        ];
    }

    public function refresh(string $refreshToken): ?array
    {
        $row = $this->refreshTokenModel
            ->where('token_hash', hash('sha256', $refreshToken))
            ->first();

        if (! $row || strtotime($row['expires_at']) < time()) {
            return null;
        }

        $profile = $this->userModel->getUserProfile((int) $row['user_id']);
        if (! $profile) {
            return null;
        }

        $config = config('TaskManagement');

        $accessToken = $this->jwtService->createToken([
            'sub' => $profile['id'],
            'email' => $profile['email'],
            'role' => $profile['role_slug'],
            'permissions' => $profile['permissions'],
        ], (int) (getenv('TASK_JWT_TTL') ?: $config->jwtTtlSeconds));

        return [
            'access_token' => $accessToken,
            'user_id' => $profile['id'],
            'role' => $profile['role_name'],
            'permissions' => $profile['permissions'],
        ];
    }

    public function logout(string $refreshToken): void
    {
        $this->refreshTokenModel->where('token_hash', hash('sha256', $refreshToken))->delete();
    }
}
