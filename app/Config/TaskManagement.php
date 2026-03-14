<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class TaskManagement extends BaseConfig
{
    public string $jwtIssuer = 'task-management-api';
    public string $jwtAudience = 'task-management-frontend';
    public string $jwtSecret = 'change-this-secret-in-env';
    public int $jwtTtlSeconds = 3600;
    public int $refreshTtlSeconds = 604800;

    public int $attachmentMaxSize = 5 * 1024 * 1024;
    public array $attachmentAllowedExtensions = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'xls', 'xlsx'];

    public string $attachmentStoragePath = WRITEPATH . 'uploads/task_attachments';
}
