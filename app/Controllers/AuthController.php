<?php

namespace App\Controllers;

use App\Models\PasswordResetModel;
use App\Models\UserModel;
use App\Services\AuthService;

class AuthController extends BaseApiController
{
    public function login()
    {
        helper('api_response');

        $payload = $this->payload();
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            return api_error('Email and password are required', 422);
        }

        $result = (new AuthService())->login($email, $password);
        if (! $result) {
            return api_error('Invalid credentials', 401);
        }

        return api_success('Login successful', $result);
    }

    public function logout()
    {
        helper('api_response');

        $payload = $this->payload();
        $refreshToken = (string) ($payload['refresh_token'] ?? '');

        if ($refreshToken !== '') {
            (new AuthService())->logout($refreshToken);
        }

        return api_success('Logout successful');
    }

    public function refreshToken()
    {
        helper('api_response');

        $payload = $this->payload();
        $refreshToken = (string) ($payload['refresh_token'] ?? '');
        if ($refreshToken === '') {
            return api_error('refresh_token is required', 422);
        }

        $result = (new AuthService())->refresh($refreshToken);
        if (! $result) {
            return api_error('Invalid refresh token', 401);
        }

        return api_success('Token refreshed', $result);
    }

    public function resetPassword()
    {
        helper('api_response');

        $payload = $this->payload();
        $user = $this->user();
        $newPassword = (string) ($payload['new_password'] ?? '');

        if (strlen($newPassword) < 8) {
            return api_error('Password must be at least 8 characters', 422);
        }

        (new UserModel())->update((int) $user['id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
        ]);

        (new PasswordResetModel())->insert([
            'user_id' => (int) $user['id'],
            'token_hash' => hash('sha256', bin2hex(random_bytes(20))),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'used_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return api_success('Password reset successful');
    }
}
