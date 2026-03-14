<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TaskManagementSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $roles = [
            ['name' => 'Admin', 'slug' => 'admin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Manager', 'slug' => 'manager', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Team Member', 'slug' => 'team_member', 'created_at' => $now, 'updated_at' => $now],
        ];
        $this->db->table('roles')->insertBatch($roles);

        $permissions = [
            ['name' => 'Create Task', 'slug' => 'create_task'],
            ['name' => 'Assign Task', 'slug' => 'assign_task'],
            ['name' => 'Update Task', 'slug' => 'update_task'],
            ['name' => 'Comment Task', 'slug' => 'comment_task'],
            ['name' => 'Extend Deadline', 'slug' => 'extend_deadline'],
            ['name' => 'Manage Users', 'slug' => 'manage_users'],
            ['name' => 'Manage Departments', 'slug' => 'manage_departments'],
        ];

        foreach ($permissions as &$permission) {
            $permission['created_at'] = $now;
            $permission['updated_at'] = $now;
        }
        unset($permission);

        $this->db->table('permissions')->insertBatch($permissions);

        $roleIds = $this->fetchMap('roles', 'slug');
        $permissionIds = $this->fetchMap('permissions', 'slug');

        $rolePermissionMap = [
            'admin' => ['create_task', 'assign_task', 'update_task', 'comment_task', 'extend_deadline', 'manage_users', 'manage_departments'],
            'manager' => ['create_task', 'assign_task', 'update_task', 'comment_task', 'extend_deadline'],
            'team_member' => ['comment_task'],
        ];

        $rolePermissions = [];
        foreach ($rolePermissionMap as $roleSlug => $permissionSlugs) {
            foreach ($permissionSlugs as $permissionSlug) {
                $rolePermissions[] = [
                    'role_id' => $roleIds[$roleSlug],
                    'permission_id' => $permissionIds[$permissionSlug],
                ];
            }
        }
        $this->db->table('role_permissions')->insertBatch($rolePermissions);

        $departments = [
            ['name' => 'Engineering', 'code' => 'ENG', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Product', 'code' => 'PRD', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sales', 'code' => 'SAL', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ];
        $this->db->table('departments')->insertBatch($departments);

        $users = [
            [
                'name' => 'Ava Johnson',
                'email' => 'admin@taskflow.io',
                'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
                'role_id' => $roleIds['admin'],
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Michael Chen',
                'email' => 'manager@taskflow.io',
                'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
                'role_id' => $roleIds['manager'],
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Priya Singh',
                'email' => 'member@taskflow.io',
                'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
                'role_id' => $roleIds['team_member'],
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $this->db->table('users')->insertBatch($users);

        $userIds = $this->fetchMap('users', 'email');
        $departmentIds = $this->fetchMap('departments', 'code');

        $userDepartments = [
            ['user_id' => $userIds['admin@taskflow.io'], 'department_id' => $departmentIds['ENG'], 'created_at' => $now],
            ['user_id' => $userIds['manager@taskflow.io'], 'department_id' => $departmentIds['PRD'], 'created_at' => $now],
            ['user_id' => $userIds['member@taskflow.io'], 'department_id' => $departmentIds['PRD'], 'created_at' => $now],
        ];
        $this->db->table('user_departments')->insertBatch($userDepartments);
    }

    private function fetchMap(string $table, string $keyColumn): array
    {
        $rows = $this->db->table($table)->select('id,' . $keyColumn)->get()->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $map[$row[$keyColumn]] = (int) $row['id'];
        }
        return $map;
    }
}
