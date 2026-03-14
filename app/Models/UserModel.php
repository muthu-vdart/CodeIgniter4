<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'email',
        'password_hash',
        'role_id',
        'is_active',
        'last_login_at',
    ];
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;

    public function findByEmail(string $email): ?array
    {
        $row = $this->select('users.*, roles.name as role_name, roles.slug as role_slug')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.email', $email)
            ->where('users.deleted_at', null)
            ->first();

        return $row ?: null;
    }

    public function getUserProfile(int $userId): ?array
    {
        $user = $this->select('users.id, users.name, users.email, users.role_id, roles.name as role_name, roles.slug as role_slug')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->find($userId);

        if (! $user) {
            return null;
        }

        $permissions = $this->db->table('role_permissions rp')
            ->select('p.slug')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->where('rp.role_id', $user['role_id'])
            ->get()
            ->getResultArray();

        $departments = $this->db->table('user_departments ud')
            ->select('d.id, d.name, d.code')
            ->join('departments d', 'd.id = ud.department_id')
            ->where('ud.user_id', $userId)
            ->get()
            ->getResultArray();

        $user['permissions'] = array_values(array_map(static fn ($row) => $row['slug'], $permissions));
        $user['departments'] = $departments;

        return $user;
    }
}
