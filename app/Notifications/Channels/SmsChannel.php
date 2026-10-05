<?php

namespace App\Notifications\Channels;

use App\Services\Sms\SmsManager;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends a notification as a text message. A failure is logged and reported
 * through the notification events, and never stops the other channels or the
 * reminder run.
 */
class SmsChannel
{
    public function __construct(private readonly SmsManager $sms)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        $phone = $this->sms->normalise($notifiable->routeNotificationFor('sms', $notification));

        if (! $phone) {
            return;
        }

        try {
            $this->sms->send($phone, $notification->toSms($notifiable));
        } catch (Throwable $exception) {
            Log::error('SMS could not be delivered.', [
                'user_id' => $notifiable->getKey(),
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);

            $notification->failedChannels[] = 'sms';

            event(new NotificationFailed($notifiable, $notification, self::class, ['message' => $exception->getMessage()]));
        }
    }
}
