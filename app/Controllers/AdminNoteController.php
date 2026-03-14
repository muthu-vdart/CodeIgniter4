<?php

namespace App\Controllers;

use App\Models\AdminNoteModel;

class AdminNoteController extends BaseApiController
{
    public function index()
    {
        helper('api_response');

        $notes = (new AdminNoteModel())
            ->select('admin_notes.id, admin_notes.title, admin_notes.content_encrypted, admin_notes.created_by, admin_notes.created_at, admin_notes.updated_at, users.name as created_by_name')
            ->join('users', 'users.id = admin_notes.created_by', 'left')
            ->orderBy('admin_notes.updated_at', 'DESC')
            ->findAll();

        $data = array_map(fn (array $note) => [
            ...$note,
            'content' => $this->decryptContent($note['content_encrypted']),
        ], $notes);

        return api_success('Admin notes fetched successfully', $data);
    }

    public function create()
    {
        helper('api_response');

        $payload = $this->payload();
        $title = trim((string) ($payload['title'] ?? ''));
        $content = (string) ($payload['content'] ?? '');

        if ($title === '' || $content === '') {
            return api_error('title and content are required', 422);
        }

        $model = new AdminNoteModel();
        $id = $model->insert([
            'title' => $title,
            'content_encrypted' => $this->encryptContent($content),
            'created_by' => (int) $this->user()['id'],
        ], true);

        return api_success('Admin note created successfully', $model->find($id), 201);
    }

    public function update($id = null)
    {
        helper('api_response');

        $model = new AdminNoteModel();
        $note = $model->find((int) $id);
        if (! $note) {
            return api_error('Note not found', 404);
        }

        $payload = $this->payload();
        $title = trim((string) ($payload['title'] ?? $note['title']));
        $content = (string) ($payload['content'] ?? $this->decryptContent($note['content_encrypted']));

        $model->update((int) $id, [
            'title' => $title,
            'content_encrypted' => $this->encryptContent($content),
        ]);

        return api_success('Admin note updated successfully', $model->find((int) $id));
    }

    public function delete($id = null)
    {
        helper('api_response');

        $model = new AdminNoteModel();
        if (! $model->find((int) $id)) {
            return api_error('Note not found', 404);
        }

        $model->delete((int) $id);
        return api_success('Admin note deleted successfully');
    }

    private function encryptContent(string $plain): string
    {
        $secret = getenv('TASK_JWT_SECRET') ?: config('TaskManagement')->jwtSecret;
        $key = hash('sha256', $secret, true);
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . $cipher);
    }

    private function decryptContent(?string $encoded): string
    {
        if (! $encoded) {
            return '';
        }

        $raw = base64_decode($encoded, true);
        if (! $raw || strlen($raw) < 17) {
            return '';
        }

        $secret = getenv('TASK_JWT_SECRET') ?: config('TaskManagement')->jwtSecret;
        $key = hash('sha256', $secret, true);
        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);

        $plain = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        return is_string($plain) ? $plain : '';
    }
}
