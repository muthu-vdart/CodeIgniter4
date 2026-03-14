<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskAssignmentModel extends Model
{
    protected $table = 'task_assignments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['task_id', 'user_id', 'assigned_by', 'assigned_at', 'status'];
    protected $useTimestamps = false;
}
