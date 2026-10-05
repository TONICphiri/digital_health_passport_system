<?php

namespace App\Listeners;

use App\Models\ReminderDelivery;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\ReminderDueNotification;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Str;
use Throwable;

/**
 * Keeps a record of every channel a reminder went through. Listens to the
 * framework's notification events, so the channels themselves stay unaware of it.
 */
class RecordReminderDelivery
{
    public function onSent(NotificationSent $event): void
    {
        $channel = $this->channelName($event->channel);

        if (! $event->notification instanceof ReminderDueNotification || ! $channel) {
            return;
        }

        // The text message channel reports its own failures and swallows them.
        if (in_array($channel, $event->notification->failedChannels, true)) {
            return;
        }

        $this->store($event->notification, $channel, ReminderDelivery::SENT);
    }

    public function onFailed(NotificationFailed $event): void
    {
        $channel = $this->channelName($event->channel);

        if (! $event->notification instanceof ReminderDueNotification || ! $channel) {
            return;
        }

        $this->store($event->notification, $channel, ReminderDelivery::FAILED, (string) ($event->data['message'] ?? 'Delivery failed.'));
    }

    private function store(ReminderDueNotification $notification, string $channel, string $status, ?string $detail = null): void
    {
        try {
            ReminderDelivery::create([
                'reminder_id' => $notification->reminderId(),
                'batch' => (string) $notification->id,
                'channel' => $channel,
                'status' => $status,
                'detail' => $detail ? Str::limit($detail, 240, '') : null,
            ]);
        } catch (Throwable) {
            // A missing delivery record must never stop a reminder from being sent.
        }
    }

    private function channelName(string $channel): ?string
    {
        return match ($channel) {
            'database' => 'portal',
            'mail' => 'email',
            SmsChannel::class => 'sms',
            default => null,
        };
    }
}
