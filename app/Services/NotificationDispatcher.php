<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Delivers notifications to a patient. Children do not have their own
 * accounts, so their notifications go to the mother's portal account.
 * A delivery failure is logged and never interrupts the clinical workflow.
 */
class NotificationDispatcher
{
    public function toPatient(Patient $patient, Notification $notification): bool
    {
        $account = $patient->portalAccount ?? $patient->mother?->portalAccount;

        if (! $account || ! $account->isActive()) {
            return false;
        }

        try {
            $account->notify($notification);

            return true;
        } catch (Throwable $exception) {
            Log::error('Notification could not be delivered.', [
                'patient_id' => $patient->id,
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
