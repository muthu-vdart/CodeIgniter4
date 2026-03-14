<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskStatusHistoryModel extends Model
{
    protected $table = 'task_status_history';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['task_id', 'from_status', 'to_status', 'changed_by', 'changed_at', 'remarks'];
    protected $useTimestamps = false;
}
