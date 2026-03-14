<?php

namespace App\Filters;

use App\Models\UserModel;
use App\Services\JwtService;
use App\Services\UserContext;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('api_response');

        $authHeader = $request->getHeaderLine('Authorization');
        if (! str_starts_with($authHeader, 'Bearer ')) {
            return api_error('Unauthorized access', 401);
        }

        $token = trim(substr($authHeader, 7));
        $payload = (new JwtService())->decodeToken($token);

        if (! $payload || empty($payload['sub'])) {
            return api_error('Invalid or expired token', 401);
        }

        $profile = (new UserModel())->getUserProfile((int) $payload['sub']);
        if (! $profile) {
            return api_error('User not found', 401);
        }

        UserContext::set($profile);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        UserContext::set(null);
    }
}
