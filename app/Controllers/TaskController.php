<?php

namespace App\Controllers;

use App\Models\TaskModel;
use App\Services\TaskService;

class TaskController extends BaseApiController
{
    public function index()
    {
        helper('api_response');

        $filters = [
            'status' => $this->request->getGet('status'),
            'priority' => $this->request->getGet('priority'),
            'department_id' => $this->request->getGet('department_id'),
            'q' => $this->request->getGet('q'),
        ];

        $page = (int) ($this->request->getGet('page') ?? 1);
        $limit = (int) ($this->request->getGet('limit') ?? 20);

        $data = (new TaskService())->list($filters, $page, $limit);
        return api_success('Tasks fetched successfully', $data);
    }

    public function show($id = null)
    {
        helper('api_response');

        $task = (new TaskService())->detail((int) $id);
        if (! $task) {
            return api_error('Task not found', 404);
        }

        return api_success('Task fetched successfully', $task);
    }

    public function create()
    {
        helper('api_response');

        $payload = $this->payload();
        if (empty($payload['title'])) {
            return api_error('Title is required', 422);
        }

        $task = (new TaskService())->create($payload, (int) $this->user()['id']);
        return api_success('Task created successfully', $task, 201);
    }

    public function update($id = null)
    {
        helper('api_response');

        $payload = $this->payload();
        $task = (new TaskService())->update((int) $id, $payload, (int) $this->user()['id']);

        if (! $task) {
            return api_error('Task not found', 404);
        }

        return api_success('Task updated successfully', $task);
    }

    public function delete($id = null)
    {
        helper('api_response');

        $deleted = (new TaskService())->delete((int) $id, (int) $this->user()['id']);
        if (! $deleted) {
            return api_error('Task not found', 404);
        }

        return api_success('Task deleted successfully');
    }

    public function assign($taskId = null)
    {
        helper('api_response');

        $payload = $this->payload();
        $assigneeIds = (array) ($payload['assignee_ids'] ?? []);

        $assignees = (new TaskService())->assign((int) $taskId, $assigneeIds, (int) $this->user()['id']);
        return api_success('Task assigned successfully', $assignees);
    }

    public function reassign($taskId = null)
    {
        return $this->assign($taskId);
    }

    public function updateStatus($taskId = null)
    {
        helper('api_response');

        $payload = $this->payload();
        $status = (string) ($payload['status'] ?? '');
        $remarks = isset($payload['remarks']) ? (string) $payload['remarks'] : null;

        if ($status === '') {
            return api_error('status is required', 422);
        }

        $task = (new TaskService())->updateStatus((int) $taskId, $status, (int) $this->user()['id'], $remarks);
        if (! $task) {
            return api_error('Task not found', 404);
        }

        return api_success('Task status updated successfully', $task);
    }

    public function uploadAttachment($taskId = null)
    {
        helper('api_response');

        $task = (new TaskModel())->find((int) $taskId);
        if (! $task) {
            return api_error('Task not found', 404);
        }

        $file = $this->request->getFile('attachment');
        if (! $file || ! $file->isValid()) {
            return api_error('Valid attachment file is required', 422);
        }

        $config = config('TaskManagement');
        $extension = strtolower($file->getExtension());

        if (! in_array($extension, $config->attachmentAllowedExtensions, true)) {
            return api_error('Invalid file type', 422);
        }

        if ($file->getSize() > $config->attachmentMaxSize) {
            return api_error('File size exceeds limit', 422);
        }

        if (! is_dir($config->attachmentStoragePath)) {
            mkdir($config->attachmentStoragePath, 0755, true);
        }

        $storedName = $file->getRandomName();
        $file->move($config->attachmentStoragePath, $storedName);

        $attachments = (new TaskService())->saveAttachment((int) $taskId, (int) $this->user()['id'], [
            'original_name' => $file->getClientName(),
            'stored_name' => $storedName,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        return api_success('Attachment uploaded successfully', $attachments, 201);
    }
}
