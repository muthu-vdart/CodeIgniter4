<?php

namespace App\Models;

use CodeIgniter\Model;

class UserDepartmentModel extends Model
{
    protected $table = 'user_departments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'department_id', 'created_at'];
    protected $useTimestamps = false;
}
