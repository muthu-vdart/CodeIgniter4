<?php

namespace App\Controllers;

use App\Models\DepartmentModel;

class DepartmentController extends BaseApiController
{
    public function index()
    {
        helper('api_response');
        $departments = (new DepartmentModel())->where('deleted_at', null)->findAll();
        return api_success('Departments fetched successfully', $departments);
    }

    public function create()
    {
        helper('api_response');
        $payload = $this->payload();

        if (empty($payload['name']) || empty($payload['code'])) {
            return api_error('name and code are required', 422);
        }

        $model = new DepartmentModel();
        $id = $model->insert([
            'name' => trim((string) $payload['name']),
            'code' => strtoupper(trim((string) $payload['code'])),
            'is_active' => (int) ($payload['is_active'] ?? 1),
        ], true);

        return api_success('Department created successfully', $model->find($id), 201);
    }

    public function update($id = null)
    {
        helper('api_response');
        $payload = $this->payload();

        $model = new DepartmentModel();
        $department = $model->find((int) $id);
        if (! $department) {
            return api_error('Department not found', 404);
        }

        $model->update((int) $id, [
            'name' => trim((string) ($payload['name'] ?? $department['name'])),
            'code' => strtoupper(trim((string) ($payload['code'] ?? $department['code']))),
            'is_active' => isset($payload['is_active']) ? (int) $payload['is_active'] : $department['is_active'],
        ]);

        return api_success('Department updated successfully', $model->find((int) $id));
    }

    public function delete($id = null)
    {
        helper('api_response');

        $model = new DepartmentModel();
        if (! $model->find((int) $id)) {
            return api_error('Department not found', 404);
        }

        $model->delete((int) $id);
        return api_success('Department deleted successfully');
    }
}
