<?php

namespace App\Commands;

use App\Models\TaskModel;
use App\Services\EmailService;
use App\Services\NotificationService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SendTaskReminderCommand extends BaseCommand
{
    protected $group = 'Task';
    protected $name = 'task:send-reminders';
    protected $description = 'Sends due-tomorrow reminders (email + in-app notifications).';

    public function run(array $params)
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $tasks = (new TaskModel())
            ->where('COALESCE(extended_due_date, due_date) =', $tomorrow)
            ->whereIn('status', ['To Do', 'In Progress', 'On Hold', 'Need Clarification'])
            ->findAll();

        $notificationService = new NotificationService();
        $emailService = new EmailService();

        foreach ($tasks as $task) {
            $assignees = db_connect()->table('task_assignments ta')
                ->select('u.id, u.email, u.name')
                ->join('users u', 'u.id = ta.user_id')
                ->where('ta.task_id', (int) $task['id'])
                ->get()
                ->getResultArray();

            if (! $assignees) {
                continue;
            }

            $userIds = array_map(static fn ($row) => (int) $row['id'], $assignees);
            $notificationService->createMany(
                $userIds,
                'Task Due Tomorrow',
                sprintf('Task %s (%s) is due tomorrow.', $task['task_code'], $task['title']),
                (int) $task['id']
            );

            foreach ($assignees as $assignee) {
                $emailService->sendTaskReminder(
                    $assignee['email'],
                    'Task due tomorrow: ' . $task['task_code'],
                    sprintf('Hi %s, task "%s" is due tomorrow (%s).', $assignee['name'], $task['title'], $tomorrow)
                );
            }
        }

        CLI::write('Reminder job completed. Tasks processed: ' . count($tasks), 'green');
    }
}
