<?php

namespace App\Notifications;

use App\Models\Backup;
use Illuminate\Support\Number;

/**
 * Tells the System Administrators that a backup finished or failed.
 * A failure is also sent by email, whatever the email setting says, because
 * nobody would otherwise know that the data is no longer being protected.
 */
class BackupNotification extends SystemNotification
{
    public function __construct(private readonly Backup $backup)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = parent::via($notifiable);

        if (! $this->backup->isCompleted() && ! empty($notifiable->email) && ! in_array('mail', $channels, true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    protected function title(): string
    {
        return $this->backup->isCompleted() ? 'Backup completed' : 'Backup failed';
    }

    protected function message(): string
    {
        if ($this->backup->isCompleted()) {
            return "A backup of the database was saved as {$this->backup->filename} (".$this->backup->readableSize().'). You can download it from the Backups page.';
        }

        return 'The backup could not be completed: '.($this->backup->error ?: 'unknown reason').' Open the Backups page and try again.';
    }

    protected function category(): string
    {
        return 'backup';
    }

    protected function actionUrl(): ?string
    {
        return route('admin.backups.index');
    }
}
