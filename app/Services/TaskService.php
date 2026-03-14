<?php

namespace App\Services;

use App\Models\TaskModel;
use App\Models\TaskAssignmentModel;
use App\Models\TaskDepartmentModel;
use App\Models\TaskStatusHistoryModel;
use App\Models\TaskDeadlineHistoryModel;
use App\Models\TaskCommentModel;
use App\Models\TaskAttachmentModel;
use CodeIgniter\Database\BaseConnection;

class TaskService
{
    private BaseConnection $db;

    public function __construct(
        private readonly TaskModel $taskModel = new TaskModel(),
        private readonly TaskAssignmentModel $taskAssignmentModel = new TaskAssignmentModel(),
        private readonly TaskDepartmentModel $taskDepartmentModel = new TaskDepartmentModel(),
        private readonly TaskStatusHistoryModel $statusHistoryModel = new TaskStatusHistoryModel(),
        private readonly TaskDeadlineHistoryModel $deadlineHistoryModel = new TaskDeadlineHistoryModel(),
        private readonly TaskCommentModel $commentModel = new TaskCommentModel(),
        private readonly TaskAttachmentModel $attachmentModel = new TaskAttachmentModel(),
        private readonly ActivityLogService $activityLogService = new ActivityLogService(),
        private readonly NotificationService $notificationService = new NotificationService()
    ) {
        $this->db = db_connect();
    }

    public function list(array $filters, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = min(100, max(1, $limit));
        $offset = ($page - 1) * $limit;

        $rows = $this->taskModel->getTaskList($filters, $limit, $offset);
        $total = $this->taskModel->getTaskCount($filters);

        foreach ($rows as &$task) {
            $task['assignees'] = $this->getAssignees((int) $task['id']);
            $task['departments'] = $this->getDepartments((int) $task['id']);
        }
        unset($task);

        return [
            'items' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ],
        ];
    }

    public function detail(int $taskId): ?array
    {
        $task = $this->taskModel->find($taskId);
        if (! $task) {
            return null;
        }

        $task['assignees'] = $this->getAssignees($taskId);
        $task['departments'] = $this->getDepartments($taskId);
        $task['comments'] = $this->db->table('task_comments tc')
            ->select('tc.*, u.name as user_name')
            ->join('users u', 'u.id = tc.user_id', 'left')
            ->where('tc.task_id', $taskId)
            ->orderBy('tc.created_at', 'DESC')
            ->get()
            ->getResultArray();
        $task['attachments'] = $this->attachmentModel
            ->where('task_id', $taskId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
        $task['status_history'] = $this->statusHistoryModel
            ->where('task_id', $taskId)
            ->orderBy('changed_at', 'DESC')
            ->findAll();

        return $task;
    }

    public function create(array $payload, int $createdBy): array
    {
        $this->db->transBegin();

        $taskCode = 'TASK-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $taskId = $this->taskModel->insert([
            'task_code' => $taskCode,
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'created_by' => $createdBy,
            'priority' => $payload['priority'] ?? 'Medium',
            'status' => $payload['status'] ?? 'To Do',
            'start_date' => $payload['start_date'] ?? null,
            'due_date' => $payload['due_date'] ?? null,
            'extended_due_date' => $payload['extended_due_date'] ?? null,
        ], true);

        $now = date('Y-m-d H:i:s');

        foreach (($payload['department_ids'] ?? []) as $departmentId) {
            $this->taskDepartmentModel->insert([
                'task_id' => $taskId,
                'department_id' => (int) $departmentId,
                'created_at' => $now,
            ]);
        }

        $assignedUserIds = [];
        foreach (($payload['assignee_ids'] ?? []) as $assigneeId) {
            $assigneeId = (int) $assigneeId;
            $assignedUserIds[] = $assigneeId;
            $this->taskAssignmentModel->insert([
                'task_id' => $taskId,
                'user_id' => $assigneeId,
                'assigned_by' => $createdBy,
                'assigned_at' => $now,
                'status' => 'Assigned',
            ]);
        }

        $this->statusHistoryModel->insert([
            'task_id' => $taskId,
            'from_status' => null,
            'to_status' => $payload['status'] ?? 'To Do',
            'changed_by' => $createdBy,
            'changed_at' => $now,
            'remarks' => 'Task created',
        ]);

        $this->activityLogService->log('task_created', $createdBy, (int) $taskId, $payload);

        if ($assignedUserIds) {
            $this->notificationService->createMany(
                $assignedUserIds,
                'New Task Assigned',
                'A new task has been assigned to you: ' . $payload['title'],
                (int) $taskId
            );
        }

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            throw new \RuntimeException('Failed to create task');
        }

        $this->db->transCommit();

        return $this->detail((int) $taskId);
    }

    public function update(int $taskId, array $payload, int $updatedBy): ?array
    {
        $task = $this->taskModel->find($taskId);
        if (! $task) {
            return null;
        }

        $updateData = [
            'title' => $payload['title'] ?? $task['title'],
            'description' => $payload['description'] ?? $task['description'],
            'priority' => $payload['priority'] ?? $task['priority'],
            'start_date' => $payload['start_date'] ?? $task['start_date'],
            'due_date' => $payload['due_date'] ?? $task['due_date'],
            'extended_due_date' => $payload['extended_due_date'] ?? $task['extended_due_date'],
        ];

        $this->taskModel->update($taskId, $updateData);

        if (isset($payload['due_date']) && $payload['due_date'] !== $task['due_date']) {
            $this->deadlineHistoryModel->insert([
                'task_id' => $taskId,
                'previous_due_date' => $task['due_date'],
                'new_due_date' => $payload['due_date'],
                'extended_by' => $updatedBy,
                'reason' => $payload['deadline_reason'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $assigneeIds = [];
        if (! empty($payload['assignee_ids']) && is_array($payload['assignee_ids'])) {
            $assigneeIds = $this->syncAssignments($taskId, $payload['assignee_ids'], $updatedBy);
        }

        if (! empty($payload['department_ids']) && is_array($payload['department_ids'])) {
            $this->syncDepartments($taskId, $payload['department_ids']);
        }

        $this->activityLogService->log('task_updated', $updatedBy, $taskId, $payload);

        if ($assigneeIds) {
            $this->notificationService->createMany(
                $assigneeIds,
                'Task Updated',
                'Task ' . $task['task_code'] . ' has been updated.',
                $taskId
            );
        }

        return $this->detail($taskId);
    }

    public function updateStatus(int $taskId, string $status, int $changedBy, ?string $remarks = null): ?array
    {
        $task = $this->taskModel->find($taskId);
        if (! $task) {
            return null;
        }

        $this->taskModel->update($taskId, ['status' => $status]);

        $this->statusHistoryModel->insert([
            'task_id' => $taskId,
            'from_status' => $task['status'],
            'to_status' => $status,
            'changed_by' => $changedBy,
            'changed_at' => date('Y-m-d H:i:s'),
            'remarks' => $remarks,
        ]);

        $assigneeIds = array_column($this->getAssignees($taskId), 'id');
        $this->notificationService->createMany(
            $assigneeIds,
            'Task Status Updated',
            sprintf('Task %s moved from %s to %s', $task['task_code'], $task['status'], $status),
            $taskId
        );

        $this->activityLogService->log('task_status_updated', $changedBy, $taskId, [
            'from' => $task['status'],
            'to' => $status,
            'remarks' => $remarks,
        ]);

        return $this->detail($taskId);
    }

    public function addComment(int $taskId, int $userId, string $commentText): array
    {
        $this->commentModel->insert([
            'task_id' => $taskId,
            'user_id' => $userId,
            'comment_text' => $commentText,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $assigneeIds = array_column($this->getAssignees($taskId), 'id');
        $this->notificationService->createMany(
            $assigneeIds,
            'Task Comment Added',
            'A new comment was added to task #' . $taskId,
            $taskId
        );

        $this->activityLogService->log('task_comment_added', $userId, $taskId, ['comment' => $commentText]);

        return $this->db->table('task_comments tc')
            ->select('tc.*, u.name as user_name')
            ->join('users u', 'u.id = tc.user_id', 'left')
            ->where('tc.task_id', $taskId)
            ->orderBy('tc.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function saveAttachment(int $taskId, int $uploadedBy, array $fileMeta): array
    {
        $this->attachmentModel->insert([
            'task_id' => $taskId,
            'uploaded_by' => $uploadedBy,
            'original_name' => $fileMeta['original_name'],
            'stored_name' => $fileMeta['stored_name'],
            'mime_type' => $fileMeta['mime_type'],
            'size_bytes' => $fileMeta['size_bytes'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->activityLogService->log('task_attachment_added', $uploadedBy, $taskId, $fileMeta);

        return $this->attachmentModel->where('task_id', $taskId)->orderBy('created_at', 'DESC')->findAll();
    }

    public function delete(int $taskId, int $deletedBy): bool
    {
        $task = $this->taskModel->find($taskId);
        if (! $task) {
            return false;
        }

        $this->taskModel->delete($taskId);
        $this->activityLogService->log('task_deleted', $deletedBy, $taskId, ['task_code' => $task['task_code']]);

        return true;
    }

    public function assign(int $taskId, array $assigneeIds, int $assignedBy): array
    {
        $ids = $this->syncAssignments($taskId, $assigneeIds, $assignedBy);
        $this->activityLogService->log('task_assigned', $assignedBy, $taskId, ['assignee_ids' => $ids]);

        $this->notificationService->createMany(
            $ids,
            'Task Assigned',
            'You have been assigned task #' . $taskId,
            $taskId
        );

        return $this->getAssignees($taskId);
    }

    private function syncAssignments(int $taskId, array $assigneeIds, int $assignedBy): array
    {
        $assigneeIds = array_values(array_unique(array_map('intval', $assigneeIds)));
        $existing = $this->taskAssignmentModel->where('task_id', $taskId)->findAll();
        $existingIds = array_map(static fn ($row) => (int) $row['user_id'], $existing);

        $toAdd = array_diff($assigneeIds, $existingIds);
        $toDelete = array_diff($existingIds, $assigneeIds);

        if ($toDelete) {
            $this->taskAssignmentModel->where('task_id', $taskId)
                ->whereIn('user_id', $toDelete)
                ->delete();
        }

        $now = date('Y-m-d H:i:s');
        foreach ($toAdd as $userId) {
            $this->taskAssignmentModel->insert([
                'task_id' => $taskId,
                'user_id' => $userId,
                'assigned_by' => $assignedBy,
                'assigned_at' => $now,
                'status' => 'Assigned',
            ]);
        }

        return $assigneeIds;
    }

    private function syncDepartments(int $taskId, array $departmentIds): void
    {
        $departmentIds = array_values(array_unique(array_map('intval', $departmentIds)));
        $existing = $this->taskDepartmentModel->where('task_id', $taskId)->findAll();
        $existingIds = array_map(static fn ($row) => (int) $row['department_id'], $existing);

        $toAdd = array_diff($departmentIds, $existingIds);
        $toDelete = array_diff($existingIds, $departmentIds);

        if ($toDelete) {
            $this->taskDepartmentModel->where('task_id', $taskId)->whereIn('department_id', $toDelete)->delete();
        }

        $now = date('Y-m-d H:i:s');
        foreach ($toAdd as $departmentId) {
            $this->taskDepartmentModel->insert([
                'task_id' => $taskId,
                'department_id' => $departmentId,
                'created_at' => $now,
            ]);
        }
    }

    private function getAssignees(int $taskId): array
    {
        return $this->db->table('task_assignments ta')
            ->select('u.id, u.name, u.email, ta.status, ta.assigned_at')
            ->join('users u', 'u.id = ta.user_id')
            ->where('ta.task_id', $taskId)
            ->get()
            ->getResultArray();
    }

    private function getDepartments(int $taskId): array
    {
        return $this->db->table('task_departments td')
            ->select('d.id, d.name, d.code')
            ->join('departments d', 'd.id = td.department_id')
            ->where('td.task_id', $taskId)
            ->get()
            ->getResultArray();
    }
}
