<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use App\Services\SettingService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base class for notifications shown in the notification centre. When email
 * notifications are switched on in the system settings, a copy is also sent
 * by email to users who have an email address. The same applies to text
 * messages for users who gave their consent.
 */
abstract class SystemNotification extends Notification
{
    /**
     * Channels that failed without throwing, so they are not also reported as sent.
     *
     * @var array<int, string>
     */
    public array $failedChannels = [];

    abstract protected function title(): string;

    abstract protected function message(): string;

    abstract protected function category(): string;

    protected function actionUrl(): ?string
    {
        return null;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $settings = app(SettingService::class);
        $channels = ['database'];

        if ($settings->get('email_notifications_enabled') === '1' && ! empty($notifiable->email) && $this->wanted($notifiable, 'mail')) {
            $channels[] = 'mail';
        }

        if ($settings->get('sms_notifications_enabled') === '1' && $notifiable->routeNotificationFor('sms') && $this->wanted($notifiable, 'sms')) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    /**
     * The in-app copy is always kept. Email and text messages follow what the
     * holder chose on the reminder preferences page.
     */
    private function wanted(object $notifiable, string $channel): bool
    {
        return ! method_exists($notifiable, 'allowsExternalNotice') || $notifiable->allowsExternalNotice($channel, $this->category());
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'category' => $this->category(),
            'url' => $this->actionUrl(),
        ];
    }

    /**
     * Text messages never carry clinical details because a phone may be shared
     * or read by someone else. They only prompt the person to open the passport.
     */
    public function toSms(object $notifiable): string
    {
        return app(SettingService::class)->systemName().': you have a new health reminder. Sign in to your portal to read it.';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting('Hello '.$notifiable->name)
            ->line($this->message());

        if ($this->actionUrl()) {
            $mail->action('Open the health passport', $this->actionUrl());
        }

        return $mail;
    }
}
