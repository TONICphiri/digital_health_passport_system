<?php

namespace App\Services;

use App\Enums\PatientStatus;
use App\Enums\RoleName;
use App\Enums\Sex;
use App\Enums\UserStatus;
use App\Exceptions\WorkflowException;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates a patient's digital health passport: the patient record, a unique
 * passport number, a QR token, emergency contacts, an optional link to the
 * mother for children, and an optional patient portal account.
 */
class PatientRegistrationService
{
    public function __construct(
        private readonly PassportNumberGenerator $numbers,
        private readonly SettingService $settings,
        private readonly AuditLogger $audit,
        private readonly PassportAccessService $access,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data  Validated patient fields.
     * @param  array<int, array<string, mixed>>  $contacts  Emergency contacts.
     * @return array{patient: Patient, temporary_password: string|null}
     */
    public function register(array $data, array $contacts, User $worker, bool $createPortalAccount): array
    {
        $isChild = Carbon::parse($data['date_of_birth'])->age < $this->settings->childSeparationAge();

        if ($isChild && empty($data['mother_id'])) {
            throw new WorkflowException('A child must be linked to the mother\'s passport. Scan the mother\'s card first.');
        }

        if (! $isChild) {
            $data['mother_id'] = null;

            if (empty($data['national_id'])) {
                throw new WorkflowException('An adult must be registered with a National ID.');
            }
        }

        if ($isChild) {
            $this->assertValidMother((int) $data['mother_id'], $worker);
        }

        return DB::transaction(function () use ($data, $contacts, $worker, $createPortalAccount) {
            $patient = Patient::create([
                ...$data,
                'national_id' => isset($data['national_id']) ? strtoupper($data['national_id']) : null,
                'passport_number' => $this->numbers->nextPassportNumber(),
                'qr_token' => $this->numbers->newQrToken(),
                'registered_facility_id' => $worker->facility_id,
                'registered_by' => $worker->id,
                'status' => PatientStatus::Active,
            ]);

            $this->syncEmergencyContacts($patient, $contacts);

            $temporaryPassword = null;

            if ($createPortalAccount) {
                $temporaryPassword = $this->createPortalAccount($patient);
            }

            $this->audit->record('patient.registered', "Issued passport {$patient->passport_number} to {$patient->full_name}.", $patient);

            // The holder is at the desk, so the new passport is open for the worker who issued it.
            $this->access->openAfterRegistration($patient, $worker);

            return ['patient' => $patient, 'temporary_password' => $temporaryPassword];
        });
    }

    /**
     * Replace the patient's emergency contacts with the submitted list.
     *
     * @param  array<int, array<string, mixed>>  $contacts
     */
    public function syncEmergencyContacts(Patient $patient, array $contacts): void
    {
        $patient->emergencyContacts()->delete();

        foreach (array_values($contacts) as $index => $contact) {
            if (empty($contact['full_name']) || empty($contact['phone'])) {
                continue;
            }

            $patient->emergencyContacts()->create([
                'full_name' => $contact['full_name'],
                'relationship' => $contact['relationship'],
                'phone' => $contact['phone'],
                'physical_address' => $contact['physical_address'] ?? null,
                'is_primary' => $index === 0,
            ]);
        }
    }

    /**
     * Creates a portal login for the patient and returns the one time password.
     */
    public function createPortalAccount(Patient $patient): string
    {
        if ($patient->portalAccount()->exists()) {
            throw new WorkflowException('This passport holder already has a portal account.');
        }

        if (empty($patient->email)) {
            throw new WorkflowException('An email address is required to create a portal account.');
        }

        if (User::query()->where('email', $patient->email)->exists()) {
            throw new WorkflowException('The email address is already used by another account.');
        }

        $temporaryPassword = Str::password(10, symbols: false);

        $account = User::create([
            'name' => $patient->full_name,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'patient_id' => $patient->id,
            'status' => UserStatus::Active,
            'must_change_password' => true,
            'password' => $temporaryPassword,
        ]);

        $account->assignRole(RoleName::Patient->value);

        return $temporaryPassword;
    }

    private function assertValidMother(int $motherId, User $worker): void
    {
        $mother = Patient::query()->find($motherId);

        if (! $mother) {
            throw new WorkflowException('The selected mother could not be found.');
        }

        if (! $this->access->hasOpen($worker, $mother)) {
            throw new WorkflowException('The mother\'s passport is not open. Scan her card again to continue.');
        }

        if ($mother->sex !== Sex::Female) {
            throw new WorkflowException('The linked parent must be the child\'s mother.');
        }

        if ($mother->isChild()) {
            throw new WorkflowException('The mother\'s own passport is still linked as a child record. Please check the details.');
        }
    }
}
