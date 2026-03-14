<?php

namespace App\Services;

class EmailService
{
    public function sendTaskReminder(string $toEmail, string $subject, string $body): bool
    {
        log_message('info', 'Reminder email queued to ' . $toEmail . ' | ' . $subject . ' | ' . $body);
        return true;
    }
}
