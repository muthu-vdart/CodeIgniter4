<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskCommentModel extends Model
{
    protected $table = 'task_comments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['task_id', 'user_id', 'comment_text', 'created_at'];
    protected $useTimestamps = false;
}
