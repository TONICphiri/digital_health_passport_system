<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send vaccination and medication reminders that are due today';

    public function handle(ReminderService $reminders): int
    {
        $sent = $reminders->sendDue();
        $this->info("{$sent} reminders delivered.");

        return self::SUCCESS;
    }
}
