<?php

namespace App\Services;

use App\Models\NotificationModel;

class NotificationService
{
    public function __construct(private readonly NotificationModel $notificationModel = new NotificationModel())
    {
    }

    public function createMany(array $userIds, string $title, string $message, ?int $taskId = null): void
    {
        $rows = [];
        $now = date('Y-m-d H:i:s');

        foreach (array_unique($userIds) as $userId) {
            $rows[] = [
                'user_id' => (int) $userId,
                'task_id' => $taskId,
                'title' => $title,
                'message' => $message,
                'is_read' => 0,
                'created_at' => $now,
            ];
        }

        if ($rows) {
            $this->notificationModel->insertBatch($rows);
        }
    }
}
