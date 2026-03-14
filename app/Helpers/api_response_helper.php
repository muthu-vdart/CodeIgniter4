<?php

if (! function_exists('api_success')) {
    function api_success(string $message, $data = null, int $statusCode = 200): \CodeIgniter\HTTP\ResponseInterface
    {
        return service('response')
            ->setStatusCode($statusCode)
            ->setJSON([
                'status' => 'success',
                'message' => $message,
                'data' => $data,
            ]);
    }
}

if (! function_exists('api_error')) {
    function api_error(string $message, int $statusCode = 400, $errors = null): \CodeIgniter\HTTP\ResponseInterface
    {
        return service('response')
            ->setStatusCode($statusCode)
            ->setJSON([
                'status' => 'error',
                'message' => $message,
                'errors' => $errors,
            ]);
    }
}
