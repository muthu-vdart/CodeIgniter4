<?php

namespace App\Controllers;

use App\Models\UserDepartmentModel;
use App\Models\UserModel;

class UserController extends BaseApiController
{
    public function index()
    {
        helper('api_response');

        $model = new UserModel();
        $users = $model->select('users.id, users.name, users.email, users.role_id, users.is_active, users.created_at, roles.name as role')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.deleted_at', null)
            ->orderBy('users.id', 'DESC')
            ->findAll();

        foreach ($users as &$user) {
            $user['departments'] = $this->userDepartments((int) $user['id']);
        }
        unset($user);

        return api_success('Users fetched successfully', $users);
    }

    public function show($id = null)
    {
        helper('api_response');

        $profile = (new UserModel())->getUserProfile((int) $id);
        if (! $profile) {
            return api_error('User not found', 404);
        }

        return api_success('User fetched successfully', $profile);
    }

    public function create()
    {
        helper('api_response');
        $payload = $this->payload();

        foreach (['name', 'email', 'password', 'role_id'] as $required) {
            if (empty($payload[$required])) {
                return api_error($required . ' is required', 422);
            }
        }

        $model = new UserModel();
        $id = $model->insert([
            'name' => trim((string) $payload['name']),
            'email' => strtolower(trim((string) $payload['email'])),
            'password_hash' => password_hash((string) $payload['password'], PASSWORD_BCRYPT),
            'role_id' => (int) $payload['role_id'],
            'is_active' => (int) ($payload['is_active'] ?? 1),
        ], true);

        $this->syncDepartments($id, (array) ($payload['department_ids'] ?? []));

        return api_success('User created successfully', (new UserModel())->getUserProfile((int) $id), 201);
    }

    public function update($id = null)
    {
        helper('api_response');
        $payload = $this->payload();

        $model = new UserModel();
        $user = $model->find((int) $id);
        if (! $user) {
            return api_error('User not found', 404);
        }

        $updateData = [
            'name' => trim((string) ($payload['name'] ?? $user['name'])),
            'email' => strtolower(trim((string) ($payload['email'] ?? $user['email']))),
            'role_id' => isset($payload['role_id']) ? (int) $payload['role_id'] : $user['role_id'],
            'is_active' => isset($payload['is_active']) ? (int) $payload['is_active'] : $user['is_active'],
        ];

        if (! empty($payload['password'])) {
            $updateData['password_hash'] = password_hash((string) $payload['password'], PASSWORD_BCRYPT);
        }

        $model->update((int) $id, $updateData);

        if (isset($payload['department_ids']) && is_array($payload['department_ids'])) {
            $this->syncDepartments((int) $id, (array) $payload['department_ids']);
        }

        return api_success('User updated successfully', $model->getUserProfile((int) $id));
    }

    public function delete($id = null)
    {
        helper('api_response');

        $model = new UserModel();
        if (! $model->find((int) $id)) {
            return api_error('User not found', 404);
        }

        $model->delete((int) $id);
        return api_success('User deleted successfully');
    }

    private function syncDepartments(int $userId, array $departmentIds): void
    {
        $departmentIds = array_values(array_unique(array_map('intval', $departmentIds)));
        $model = new UserDepartmentModel();

        $existing = $model->where('user_id', $userId)->findAll();
        $existingIds = array_map(static fn ($row) => (int) $row['department_id'], $existing);

        $toAdd = array_diff($departmentIds, $existingIds);
        $toRemove = array_diff($existingIds, $departmentIds);

        if ($toRemove) {
            $model->where('user_id', $userId)->whereIn('department_id', $toRemove)->delete();
        }

        $now = date('Y-m-d H:i:s');
        foreach ($toAdd as $departmentId) {
            $model->insert([
                'user_id' => $userId,
                'department_id' => $departmentId,
                'created_at' => $now,
            ]);
        }
    }

    private function userDepartments(int $userId): array
    {
        return db_connect()->table('user_departments ud')
            ->select('d.id, d.name, d.code')
            ->join('departments d', 'd.id = ud.department_id')
            ->where('ud.user_id', $userId)
            ->get()
            ->getResultArray();
    }
}
