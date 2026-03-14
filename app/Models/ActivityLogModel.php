<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table = 'activity_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'task_id', 'action', 'payload_json', 'ip_address', 'created_at'];
    protected $useTimestamps = false;
}
