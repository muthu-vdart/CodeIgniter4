<?php

namespace App\Controllers;

use App\Models\AdminDriveFileModel;

class AdminDriveController extends BaseApiController
{
    public function index()
    {
        helper('api_response');

        $departmentId = $this->request->getGet('department_id');
        $fileType = $this->request->getGet('file_type');

        $builder = (new AdminDriveFileModel())
            ->select('admin_drive_files.*, departments.name as department_name, users.name as uploaded_by_name')
            ->join('departments', 'departments.id = admin_drive_files.department_id', 'left')
            ->join('users', 'users.id = admin_drive_files.uploaded_by', 'left');

        if ($departmentId) {
            $builder->where('admin_drive_files.department_id', (int) $departmentId);
        }
        if ($fileType) {
            $builder->where('admin_drive_files.file_type', $fileType);
        }

        $files = $builder->orderBy('admin_drive_files.created_at', 'DESC')->findAll();
        return api_success('Admin drive files fetched successfully', $files);
    }

    public function upload()
    {
        helper('api_response');

        $file = $this->request->getFile('file');
        $departmentId = (int) ($this->request->getPost('department_id') ?? 0);

        if (! $file || ! $file->isValid()) {
            return api_error('Valid file is required', 422);
        }
        if ($departmentId <= 0) {
            return api_error('department_id is required', 422);
        }

        $config = config('TaskManagement');
        $extension = strtolower($file->getExtension());

        if (! in_array($extension, $config->attachmentAllowedExtensions, true)) {
            return api_error('Invalid file type', 422);
        }

        if ($file->getSize() > $config->attachmentMaxSize) {
            return api_error('File size exceeds limit', 422);
        }

        $storagePath = WRITEPATH . 'uploads/admin_drive';
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $storedName = $file->getRandomName();
        $file->move($storagePath, $storedName);

        $model = new AdminDriveFileModel();
        $id = $model->insert([
            'department_id' => $departmentId,
            'uploaded_by' => (int) $this->user()['id'],
            'original_name' => $file->getClientName(),
            'stored_name' => $storedName,
            'file_type' => $extension,
            'size_bytes' => $file->getSize(),
            'created_at' => date('Y-m-d H:i:s'),
        ], true);

        return api_success('File uploaded successfully', $model->find((int) $id), 201);
    }
}
