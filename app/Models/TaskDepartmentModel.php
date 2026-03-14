<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskDepartmentModel extends Model
{
    protected $table = 'task_departments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['task_id', 'department_id', 'created_at'];
    protected $useTimestamps = false;
}
