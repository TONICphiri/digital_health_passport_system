<?php

namespace App\Notifications;

use App\Models\Backup;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Tells the System Administrators that a backup finished or failed.
 * Both outcomes are always sent by email as well as shown in the notification
 * centre, whatever the general email setting says. A finished backup carries a
 * link to download it, and a failure is mailed because nobody would otherwise
 * know that the data is no longer being protected.
 */
class BackupNotification extends SystemNotification
{
    public function __construct(private readonly Backup $backup)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = parent::via($notifiable);

        if (! empty($notifiable->email) && ! in_array('mail', $channels, true)) {
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
            return "A backup of the database was saved as {$this->backup->filename} (".$this->backup->readableSize().'). You can download it from the Backups page or with the link in your email.';
        }

        return 'The backup could not be completed: '.($this->backup->error ?: 'unknown reason').' Open the Backups page and try again.';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting('Hello '.$notifiable->name)
            ->line($this->message());

        if ($this->backup->isCompleted()) {
            // The link opens the download only after the administrator has signed in.
            $mail->action('Download the backup', route('admin.backups.download', $this->backup));
        } else {
            $mail->action('Open the Backups page', route('admin.backups.index'));
        }

        return $mail;
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