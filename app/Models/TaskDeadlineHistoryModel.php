<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskDeadlineHistoryModel extends Model
{
    protected $table = 'task_deadline_history';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['task_id', 'previous_due_date', 'new_due_date', 'extended_by', 'reason', 'created_at'];
    protected $useTimestamps = false;
}
