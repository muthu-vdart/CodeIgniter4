<?php

namespace App\Filters;

use App\Services\RbacService;
use App\Services\UserContext;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('api_response');

        $user = UserContext::get();
        if (! $user) {
            return api_error('Unauthorized access', 401);
        }

        $permission = $arguments[0] ?? null;
        if (! $permission) {
            return api_error('Permission filter misconfigured', 500);
        }

        $hasPermission = (new RbacService())->userHasPermission((int) $user['id'], (string) $permission);
        if (! $hasPermission) {
            return api_error('Forbidden: missing permission ' . $permission, 403);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
