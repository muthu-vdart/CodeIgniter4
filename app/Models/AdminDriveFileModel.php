<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminDriveFileModel extends Model
{
    protected $table = 'admin_drive_files';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'department_id',
        'uploaded_by',
        'original_name',
        'stored_name',
        'file_type',
        'size_bytes',
        'created_at',
    ];
    protected $useTimestamps = false;
}
