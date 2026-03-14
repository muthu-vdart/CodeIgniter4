<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminNoteModel extends Model
{
    protected $table = 'admin_notes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['title', 'content_encrypted', 'created_by'];
    protected $useTimestamps = true;
}
