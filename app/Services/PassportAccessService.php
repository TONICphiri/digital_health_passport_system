<?php

namespace App\Services;

use App\Enums\AccessMethod;
use App\Enums\PatientStatus;
use App\Exceptions\WorkflowException;
use App\Models\PassportAccess;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Decides when a health worker may read or add to a passport.
 *
 * The rule is simple: a passport is closed until the holder is present. The
 * health worker proves that by scanning the QR code on the card. When the card
 * is not at hand, the holder's details must match instead. Either way an access
 * is recorded, stays open for a limited time, and is shown to the holder.
 */
class PassportAccessService
{
    private const NO_MATCH = 'No passport matches those details. Check them and try again.';

    public function __construct(
        private readonly QrCodeService $qr,
        private readonly SettingService $settings,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * Opens a passport from the value read from the QR code on the card.
     */
    public function openByScan(string $scanned, User $worker): PassportAccess
    {
        $token = $this->qr->tokenFromScan($scanned);
        $patient = $token ? Patient::query()->where('qr_token', $token)->first() : null;

        if (! $patient) {
            throw new WorkflowException('That code is not a health passport card. Scan the code on the card again.');
        }

        return $this->open($patient, $worker, AccessMethod::QrScan);
    }

    /**
     * Opens a passport when the card is not available. The holder's date of
     * birth must match, so the number alone is never enough.
     */
    public function openByIdentity(string $identifier, string $dateOfBirth, User $worker): PassportAccess
    {
        $identifier = strtoupper(trim($identifier));

        $patient = Patient::query()->where('passport_number', $identifier)->first();
        $method = AccessMethod::PassportNumber;

        if (! $patient) {
            $patient = Patient::query()->where('national_id', $identifier)->first();
            $method = AccessMethod::IdentityCheck;
        }

        if (! $patient || ! $patient->date_of_birth->isSameDay(Carbon::parse($dateOfBirth))) {
            throw new WorkflowException(self::NO_MATCH);
        }

        return $this->open($patient, $worker, $method);
    }

    /**
     * Used right after registration, when the holder is standing at the desk.
     */
    public function openAfterRegistration(Patient $patient, User $worker): PassportAccess
    {
        return $this->open($patient, $worker, AccessMethod::Registration);
    }

    public function open(Patient $patient, User $worker, AccessMethod $method): PassportAccess
    {
        if (! $worker->facility_id) {
            throw new WorkflowException('Your account is not linked to a facility.');
        }

        if ($patient->status === PatientStatus::Inactive) {
            throw new WorkflowException('This passport is inactive. Ask the Facility Administrator for help.');
        }

        $existing = $this->activeFor($worker, $patient);

        if ($existing) {
            $existing->update(['expires_at' => $this->expiry()]);

            return $existing;
        }

        $access = PassportAccess::create([
            'patient_id' => $patient->id,
            'user_id' => $worker->id,
            'facility_id' => $worker->facility_id,
            'method' => $method,
            'opened_at' => now(),
            'expires_at' => $this->expiry(),
        ]);

        $this->audit->record('passport.opened', "Opened the passport of {$patient->full_name}: {$method->label()}.", $patient);

        return $access;
    }

    public function activeFor(User $worker, Patient $patient): ?PassportAccess
    {
        return PassportAccess::query()
            ->where('user_id', $worker->id)
            ->where('patient_id', $patient->id)
            ->open()
            ->latest('opened_at')
            ->first();
    }

    public function hasOpen(User $worker, Patient $patient): bool
    {
        return $this->activeFor($worker, $patient) !== null;
    }

    public function close(PassportAccess $access): void
    {
        $access->update(['closed_at' => now()]);
    }

    private function expiry(): Carbon
    {
        return now()->addMinutes($this->settings->passportSessionMinutes());
    }
}
