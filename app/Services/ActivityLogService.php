<?php

namespace App\Services;

use App\Models\ActivityLogModel;

class ActivityLogService
{
    public function __construct(private readonly ActivityLogModel $activityLogModel = new ActivityLogModel())
    {
    }

    public function log(string $action, ?int $userId = null, ?int $taskId = null, ?array $payload = null): void
    {
        $this->activityLogModel->insert([
            'user_id' => $userId,
            'task_id' => $taskId,
            'action' => $action,
            'payload_json' => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => service('request')->getIPAddress(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
