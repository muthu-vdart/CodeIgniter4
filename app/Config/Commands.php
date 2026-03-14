<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Commands extends BaseConfig
{
    public $commands = [
        'task:send-reminders' => \App\Commands\SendTaskReminderCommand::class,
    ];
}
