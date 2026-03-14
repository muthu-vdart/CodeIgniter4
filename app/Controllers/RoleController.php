<?php

namespace App\Controllers;

use App\Models\RoleModel;

class RoleController extends BaseApiController
{
    public function index()
    {
        helper('api_response');

        $roles = (new RoleModel())
            ->select('id, name, slug')
            ->orderBy('id', 'ASC')
            ->findAll();

        return api_success('Roles fetched successfully', $roles);
    }
}
