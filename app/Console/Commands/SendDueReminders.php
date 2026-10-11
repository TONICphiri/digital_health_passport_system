<?php

namespace App\Console\Commands;

use App\Services\ImmunisationStatusService;
use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Create overdue immunisation reminders, then send the reminders that are due today';

    public function handle(ReminderService $reminders, ImmunisationStatusService $immunisations): int
    {
        $immunisations->syncAll();
        $sent = $reminders->sendDue();
        $this->info("{$sent} reminders delivered.");

        return self::SUCCESS;
    }
}
