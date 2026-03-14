<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskModel extends Model
{
    protected $table = 'tasks';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'task_code',
        'title',
        'description',
        'created_by',
        'priority',
        'status',
        'start_date',
        'due_date',
        'extended_due_date',
    ];
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;

    public function getTaskList(array $filters, int $limit, int $offset): array
    {
        $builder = $this->select('tasks.*, users.name AS created_by_name')
            ->join('users', 'users.id = tasks.created_by', 'left');
        $this->applyFilters($builder, $filters);

        $rows = $builder
            ->orderBy('tasks.created_at', 'DESC')
            ->findAll($limit, $offset);

        return $rows;
    }

    public function getTaskCount(array $filters): int
    {
        $builder = $this->builder()->select('tasks.id');
        $this->applyFilters($builder, $filters);
        return (int) $builder->countAllResults();
    }

    private function applyFilters($builder, array $filters): void
    {
        if (! empty($filters['status'])) {
            $builder->where('tasks.status', $filters['status']);
        }
        if (! empty($filters['priority'])) {
            $builder->where('tasks.priority', $filters['priority']);
        }
        if (! empty($filters['department_id'])) {
            $builder->join('task_departments td', 'td.task_id = tasks.id')
                ->where('td.department_id', (int) $filters['department_id']);
        }
        if (! empty($filters['q'])) {
            $builder->groupStart()
                ->like('tasks.title', $filters['q'])
                ->orLike('tasks.task_code', $filters['q'])
                ->groupEnd();
        }
    }
}
