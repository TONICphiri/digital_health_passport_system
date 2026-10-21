<?php

namespace App\Notifications\Channels;

use App\Enums\ReminderStatus;
use App\Models\ReminderDelivery;
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
            if (property_exists($notification, 'smsChannelUsed')) {
                $notification->smsChannelUsed = false;
            }

            return;
        }

        if (property_exists($notification, 'smsChannelUsed')) {
            $notification->smsChannelUsed = true;
        }

        $driver = config('services.sms.driver');

        if ($driver === 'log') {
            if (property_exists($notification, 'smsDeliveryStatus')) {
                $notification->smsDeliveryStatus = ReminderDelivery::SKIPPED;
                $notification->smsDeliveryError = 'SMS driver is set to log; messages are not sent in this environment.';
            }

            $notification->failedChannels[] = 'sms';

            event(new NotificationFailed($notifiable, $notification, self::class, ['message' => 'SMS driver is log.']));

            return;
        }

        try {
            $this->sms->send($phone, $notification->toSms($notifiable));

            if (property_exists($notification, 'smsDeliveryStatus')) {
                $notification->smsDeliveryStatus = ReminderDelivery::SENT;
            }
        } catch (Throwable $exception) {
            Log::error('SMS could not be delivered.', [
                'user_id' => $notifiable->getKey(),
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);

            if (property_exists($notification, 'smsDeliveryStatus')) {
                $notification->smsDeliveryStatus = ReminderDelivery::FAILED;
                $notification->smsDeliveryError = $exception->getMessage();
            }

            $notification->failedChannels[] = 'sms';

            event(new NotificationFailed($notifiable, $notification, self::class, ['message' => $exception->getMessage()]));
        }
    }
}
