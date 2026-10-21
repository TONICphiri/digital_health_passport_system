<?php

namespace App\Services;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\ReminderDelivery;
use App\Notifications\ReminderDueNotification;
use Illuminate\Support\Str;

/**
 * Sends vaccination and medication reminders that are due. Repeating
 * reminders, such as a monthly antiretroviral therapy refill, move to their
 * next due date after sending. Runs every morning from the scheduler.
 *
 * A reminder is only advanced (due date moved forward or marked completed)
 * when delivery actually succeeded. Failed deliveries stay in the due queue
 * so they are retried the next day.
 */
class ReminderService
{
    public function __construct(private readonly NotificationDispatcher $notifier)
    {
    }

    /**
     * @return int Number of reminders delivered.
     */
    public function sendDue(): int
    {
        $sent = 0;

        Reminder::query()->due()->with('patient.portalAccount', 'patient.mother.portalAccount')
            ->each(function (Reminder $reminder) use (&$sent) {
                $notification = new ReminderDueNotification($reminder);
                $delivered = $this->notifier->toPatient($reminder->patient, $notification);

                if ($delivered) {
                    $sent++;
                    $this->advance($reminder);

                    return;
                }

                // Delivery failed (no active portal account). Record why,
                // but do NOT advance the reminder — it stays due and is
                // retried the next scheduler run.
                if (! $reminder->deliveries()->where('batch', 'like', 'none-%')->whereDate('created_at', today())->exists()) {
                    ReminderDelivery::create([
                        'reminder_id' => $reminder->id,
                        'batch' => 'none-'.Str::uuid(),
                        'channel' => 'portal',
                        'status' => ReminderDelivery::SKIPPED,
                        'detail' => 'No active portal account for this passport holder.',
                    ]);
                }
            });

        return $sent;
    }

    private function advance(Reminder $reminder): void
    {
        if ($reminder->repeat_every_days) {
            $nextDue = $reminder->due_on->copy();

            while ($nextDue->lte(today())) {
                $nextDue->addDays($reminder->repeat_every_days);
            }

            $reminder->update(['due_on' => $nextDue, 'last_sent_at' => now()]);

            return;
        }

        $reminder->update(['last_sent_at' => now(), 'status' => ReminderStatus::Completed]);
    }
}
