<?php

namespace App\Controllers;

use App\Services\UserContext;
use CodeIgniter\RESTful\ResourceController;

class BaseApiController extends ResourceController
{
    protected $format = 'json';

    protected function user(): ?array
    {
        return UserContext::get();
    }

    protected function payload(): array
    {
        $input = $this->request->getJSON(true);
        return is_array($input) ? $input : (array) $this->request->getPost();
    }
}
