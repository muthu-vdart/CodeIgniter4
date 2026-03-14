<?php

namespace App\Services;

class JwtService
{
    public function createToken(array $claims, int $ttlSeconds): string
    {
        $config = config('TaskManagement');
        $now = time();

        $payload = array_merge($claims, [
            'iss' => getenv('TASK_JWT_ISSUER') ?: $config->jwtIssuer,
            'aud' => getenv('TASK_JWT_AUDIENCE') ?: $config->jwtAudience,
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ]);

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $secret = getenv('TASK_JWT_SECRET') ?: $config->jwtSecret;

        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));

        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    public function decodeToken(string $token): ?array
    {
        $config = config('TaskManagement');
        $secret = getenv('TASK_JWT_SECRET') ?: $config->jwtSecret;

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true));
        if (! hash_equals($expectedSignature, $signatureEncoded)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true, 512, JSON_THROW_ON_ERROR);

        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }

    private function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $input): string
    {
        return base64_decode(strtr($input, '-_', '+/') . str_repeat('=', (4 - strlen($input) % 4) % 4));
    }
}
