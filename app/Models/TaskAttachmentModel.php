<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskAttachmentModel extends Model
{
    protected $table = 'task_attachments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'task_id',
        'uploaded_by',
        'original_name',
        'stored_name',
        'mime_type',
        'size_bytes',
        'created_at',
    ];
    protected $useTimestamps = false;
}
