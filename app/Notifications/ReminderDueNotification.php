<?php

namespace App\Notifications;

use App\Models\Reminder;

class ReminderDueNotification extends SystemNotification
{
    public function __construct(private readonly Reminder $reminder)
    {
    }

    public function reminderId(): int
    {
        return $this->reminder->id;
    }

    protected function title(): string
    {
        // Confidential reminders, such as HIV treatment, use a neutral title
        // so the notification does not reveal the condition.
        return $this->reminder->is_confidential ? 'Medication reminder' : $this->reminder->title;
    }

    protected function message(): string
    {
        $patient = $this->reminder->patient;
        $prefix = $patient->mother_id ? "For {$patient->full_name}: " : '';

        return $this->reminder->is_confidential
            ? $prefix.'You have a medication refill or clinic visit due. Please visit your health facility.'
            : $prefix.$this->reminder->message;
    }

    protected function category(): string
    {
        return $this->reminder->category->value;
    }

    protected function actionUrl(): ?string
    {
        return route('portal.records');
    }
}
