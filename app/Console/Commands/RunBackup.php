<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Services\BackupService;
use App\Services\SettingService;
use Illuminate\Console\Command;

class RunBackup extends Command
{
    protected $signature = 'backup:run {--scheduled : Skip the backup when automatic backups are switched off}';

    protected $description = 'Save an encrypted copy of the database';

    public function handle(BackupService $backups, SettingService $settings): int
    {
        if ($this->option('scheduled') && $settings->get('backup_enabled', '1') !== '1') {
            $this->info('Automatic backups are switched off.');

            return self::SUCCESS;
        }

        $backup = $backups->run($this->option('scheduled') ? 'scheduled' : 'manual');

        if ($backup->status !== Backup::COMPLETED) {
            $this->error("Backup failed: {$backup->error}");

            return self::FAILURE;
        }

        $this->info("Backup saved as {$backup->filename}.");

        return self::SUCCESS;
    }
}
