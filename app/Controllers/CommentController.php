<?php

namespace App\Controllers;

use App\Models\TaskCommentModel;
use App\Services\TaskService;

class CommentController extends BaseApiController
{
    public function index($taskId = null)
    {
        helper('api_response');

        $comments = (new TaskCommentModel())
            ->select('task_comments.*, users.name as user_name')
            ->join('users', 'users.id = task_comments.user_id', 'left')
            ->where('task_comments.task_id', (int) $taskId)
            ->orderBy('task_comments.created_at', 'DESC')
            ->findAll();

        return api_success('Comments fetched successfully', $comments);
    }

    public function create($taskId = null)
    {
        helper('api_response');

        $payload = $this->payload();
        $commentText = trim((string) ($payload['comment_text'] ?? ''));

        if ($commentText === '') {
            return api_error('comment_text is required', 422);
        }

        $comments = (new TaskService())->addComment((int) $taskId, (int) $this->user()['id'], $commentText);
        return api_success('Comment added successfully', $comments, 201);
    }
}
