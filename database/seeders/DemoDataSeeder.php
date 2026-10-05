<?php

namespace Database\Seeders;

use App\Enums\AccessMethod;
use App\Enums\FacilityStatus;
use App\Enums\ReminderCategory;
use App\Enums\ReminderStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\District;
use App\Models\Facility;
use App\Models\PassportAccess;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vaccine;
use App\Services\EncounterService;
use App\Services\PatientRegistrationService;
use App\Services\VaccinationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demonstration data for presentations and testing. Records are created
 * through the same services the screens use, so every business rule applies.
 * All demonstration accounts share the password in ADMIN_PASSWORD.
 */
class DemoDataSeeder extends Seeder
{
    private string $password;

    public function __construct(
        private readonly PatientRegistrationService $registration,
        private readonly EncounterService $encounters,
        private readonly VaccinationService $vaccinations,
    ) {
        $this->password = config('health_passport.admin.password');
    }

    public function run(): void
    {
        if (Facility::query()->exists()) {
            $this->command?->warn('Demonstration data already exists. Skipped.');

            return;
        }

        $ndirande = $this->facility('Ndirande Community Hospital', 'NDH-001', 'Community Hospital', 'Government', 'Blantyre', 'Ndirande Township, Blantyre', '+265 1 870 212');
        $zomba = $this->facility('Zomba Central Hospital', 'ZCH-001', 'Central Hospital', 'Government', 'Zomba', 'Kamuzu Highway, Zomba', '+265 1 527 050');

        $this->staff($ndirande, RoleName::FacilityAdmin, 'Martha Kumwenda', 'facility@healthpassport.mw', 'Hospital Administrator');
        $worker = $this->staff($ndirande, RoleName::HealthWorker, 'Dr Chisomo Mvula', 'healthworker@healthpassport.mw', 'Medical Officer', 'MCM-11820');
        $second = $this->staff($ndirande, RoleName::HealthWorker, 'Alinafe Gondwe', 'healthworker2@healthpassport.mw', 'Registered Nurse', 'NMCM-20417');
        $this->staff($zomba, RoleName::FacilityAdmin, 'Lucy Nyirenda', 'zomba.admin@healthpassport.mw', 'Hospital Administrator');
        $this->staff($zomba, RoleName::HealthWorker, 'Dr Samuel Banda', 'zomba.healthworker@healthpassport.mw', 'Physician', 'MCM-09211');

        $this->passports($worker, $second);

        // Demonstration passports are left closed, as they would be at the end of a working day.
        PassportAccess::query()->whereNull('closed_at')->update(['closed_at' => now()]);
        auth()->logout();
    }

    private function facility(string $name, string $code, string $type, string $ownership, string $district, string $address, string $phone): Facility
    {
        return Facility::create([
            'name' => $name,
            'code' => $code,
            'type' => $type,
            'ownership' => $ownership,
            'district_id' => District::query()->where('name', $district)->value('id'),
            'physical_address' => $address,
            'phone' => $phone,
            'email' => strtolower(str_replace(' ', '.', $name)).'@health.gov.mw',
            'status' => FacilityStatus::Active,
        ]);
    }

    private function staff(Facility $facility, RoleName $role, string $name, string $email, string $title, ?string $registration = null): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => '+265 99'.random_int(1000000, 9999999),
            'job_title' => $title,
            'professional_registration_number' => $registration,
            'facility_id' => $facility->id,
            'status' => UserStatus::Active,
            'must_change_password' => false,
            'password' => $this->password,
        ]);

        return tap($user)->assignRole($role->value);
    }

    private function passports(User $worker, User $second): void
    {
        auth()->setUser($worker);

        $blantyre = District::query()->where('name', 'Blantyre')->value('id');
        $contact = fn (string $name, string $relationship, string $phone) => [['full_name' => $name, 'relationship' => $relationship, 'phone' => $phone, 'physical_address' => null]];

        // A mother with a portal account, and a baby linked to her passport.
        $grace = $this->register([
            'district_id' => $blantyre, 'national_id' => 'KT7Y4M21', 'first_name' => 'Grace', 'last_name' => 'Banda', 'date_of_birth' => '1994-03-12', 'sex' => 'female',
            'phone' => '+265 991 204 118', 'email' => 'patient@healthpassport.mw', 'village' => 'Ndirande', 'traditional_authority' => 'Kapeni',
            'physical_address' => 'Ndirande Township, House 42', 'occupation' => 'Teacher', 'blood_group' => 'O+',
            'allergies' => 'Penicillin', 'chronic_conditions' => 'HIV, on antiretroviral therapy',
        ], $contact('Thomas Banda', 'Spouse', '+265 888 310 442'), $worker, portal: true);

        $daniel = $this->register([
            'district_id' => $blantyre, 'mother_id' => $grace->id, 'first_name' => 'Daniel', 'last_name' => 'Banda', 'date_of_birth' => now()->subMonths(3)->toDateString(), 'sex' => 'male',
            'village' => 'Ndirande', 'physical_address' => 'Ndirande Township, House 42', 'blood_group' => 'O+',
        ], $contact('Grace Banda', 'Mother', '+265 991 204 118'), $worker);

        $john = $this->register([
            'district_id' => $blantyre, 'national_id' => 'PL2Q8X55', 'first_name' => 'John', 'last_name' => 'Phiri', 'date_of_birth' => '1978-11-02', 'sex' => 'male',
            'phone' => '+265 999 552 870', 'village' => 'Chilomoni', 'occupation' => 'Driver', 'blood_group' => 'A+', 'chronic_conditions' => 'Hypertension',
        ], $contact('Esther Phiri', 'Spouse', '+265 881 220 961'), $worker);

        $mary = $this->register([
            'district_id' => $blantyre, 'national_id' => 'MW4R6T90', 'first_name' => 'Mary', 'last_name' => 'Mwale', 'date_of_birth' => '1989-06-24', 'sex' => 'female',
            'phone' => '+265 995 118 004', 'village' => 'Bangwe', 'occupation' => 'Trader', 'blood_group' => 'B+',
        ], $contact('Paul Mwale', 'Brother', '+265 884 773 120'), $worker);

        $peter = $this->register([
            'district_id' => $blantyre, 'national_id' => 'ZX9C3V12', 'first_name' => 'Peter', 'last_name' => 'Chirwa', 'date_of_birth' => '1965-01-15', 'sex' => 'male',
            'phone' => '+265 997 002 318', 'village' => 'Limbe', 'occupation' => 'Farmer', 'blood_group' => 'AB+', 'chronic_conditions' => 'Type 2 diabetes',
        ], $contact('Anne Chirwa', 'Daughter', '+265 882 640 555'), $worker);

        $esther = $this->register([
            'district_id' => $blantyre, 'national_id' => 'QN5B7L33', 'first_name' => 'Esther', 'last_name' => 'Nkhoma', 'date_of_birth' => '2001-09-30', 'sex' => 'female',
            'phone' => '+265 996 481 227', 'village' => 'Chinyonga', 'occupation' => 'Student', 'blood_group' => 'O-',
        ], $contact('Rose Nkhoma', 'Mother', '+265 885 914 002'), $worker);

        // Earlier encounters. Each one starts with a scan of the card.
        $this->at(now()->subDays(14)->setTime(9, 15), fn () => $this->encounter($john, $worker, [
            'reason' => 'Headache and dizziness', 'diagnosis' => 'Hypertension, uncontrolled',
            'treatment_summary' => 'Blood pressure high on two readings. Reduce salt, return for review in two weeks.',
            'temperature' => 36.8, 'weight' => 82.0, 'height' => 174.0, 'systolic_pressure' => 162, 'diastolic_pressure' => 98, 'pulse_rate' => 84, 'oxygen_saturation' => 98,
        ], [['name' => 'Amlodipine', 'dosage' => '5 mg', 'frequency' => 'Once daily', 'duration_days' => 30]]));

        $this->at(now()->subDays(20)->setTime(10, 30), fn () => $this->encounter($grace, $worker, [
            'reason' => 'Postnatal review', 'diagnosis' => 'Normal postnatal recovery',
            'treatment_summary' => 'Continue iron supplements for one month. Family planning counselling given.',
            'temperature' => 36.6, 'weight' => 63.0, 'systolic_pressure' => 118, 'diastolic_pressure' => 76, 'pulse_rate' => 76, 'oxygen_saturation' => 99,
        ], [['name' => 'Ferrous Sulphate', 'dosage' => '1 tablet', 'frequency' => 'Once daily', 'duration_days' => 30]]));

        $this->at(now()->subDays(30)->setTime(10, 0), fn () => $this->encounter($esther, $second, [
            'reason' => 'High fever and vomiting', 'diagnosis' => 'Severe malaria',
            'treatment_summary' => 'Referred to the district hospital for intravenous treatment. Sleep under a treated mosquito net.',
            'temperature' => 39.4, 'weight' => 58.0, 'systolic_pressure' => 104, 'diastolic_pressure' => 66, 'pulse_rate' => 112, 'oxygen_saturation' => 96,
        ], []));

        $this->at(now()->subDays(2)->setTime(11, 0), fn () => $this->encounter($peter, $worker, [
            'reason' => 'Routine diabetes review', 'diagnosis' => 'Type 2 diabetes',
            'treatment_summary' => 'Blood sugar 11.2 mmol per litre. Continue treatment and diet advice.',
            'follow_up_on' => now()->addDays(12)->toDateString(),
            'temperature' => 36.5, 'weight' => 91.5, 'height' => 170.0, 'systolic_pressure' => 138, 'diastolic_pressure' => 86, 'pulse_rate' => 78, 'oxygen_saturation' => 97,
        ], [['name' => 'Metformin', 'dosage' => '500 mg', 'frequency' => 'Twice daily', 'duration_days' => 30]]));

        $this->encounter($mary, $second, [
            'reason' => 'Cough and chest pain', 'diagnosis' => 'Chest infection',
            'treatment_summary' => 'Rest, fluids and a short antibiotic course. Return if breathing becomes difficult.',
            'temperature' => 37.8, 'weight' => 61.0, 'systolic_pressure' => 112, 'diastolic_pressure' => 72, 'pulse_rate' => 90, 'oxygen_saturation' => 96,
        ], [['name' => 'Amoxicillin', 'dosage' => '500 mg', 'frequency' => 'Three times daily', 'duration_days' => 5]]);

        // Daniel's vaccinations, with the next dose reminder created automatically.
        $this->at(now()->subMonths(3)->addDay()->setTime(9, 0), function () use ($daniel, $second) {
            $this->scan($daniel, $second);
            auth()->setUser($second);
            foreach (['BCG', 'Oral Polio Vaccine'] as $name) {
                $this->vaccinations->record($daniel, Vaccine::query()->where('name', $name)->firstOrFail(), ['administered_on' => now()->toDateString(), 'batch_number' => 'MW'.random_int(10000, 99999)], $second);
            }
            auth()->setUser($second);
        });

        // Confidential reminder: the notification does not name the treatment.
        Reminder::create([
            'patient_id' => $grace->id,
            'category' => ReminderCategory::Medication,
            'title' => 'Medication refill',
            'message' => 'Collect your next three month supply at your clinic.',
            'due_on' => now()->addDays(5)->toDateString(),
            'repeat_every_days' => 90,
            'is_confidential' => true,
            'status' => ReminderStatus::Active,
            'created_by' => $worker->id,
        ]);

        auth()->setUser($worker);
    }

    /**
     * Opens the passport by scan, as the health worker would at the desk, then
     * adds the encounter.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $medications
     */
    private function encounter(Patient $patient, User $worker, array $data, array $medications): void
    {
        auth()->setUser($worker);
        $this->scan($patient, $worker);
        $this->encounters->record($patient, $data, $medications, $worker);
    }

    private function scan(Patient $patient, User $worker): void
    {
        PassportAccess::create([
            'patient_id' => $patient->id,
            'user_id' => $worker->id,
            'facility_id' => $worker->facility_id,
            'method' => AccessMethod::QrScan,
            'opened_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'closed_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $contacts
     */
    private function register(array $data, array $contacts, User $worker, bool $portal = false): Patient
    {
        $patient = $this->registration->register($data, $contacts, $worker, $portal)['patient'];

        if ($portal) {
            $patient->portalAccount->update(['password' => $this->password, 'must_change_password' => false]);
        }

        return $patient;
    }

    /**
     * Runs the callback as if it were happening at the given time, so the
     * demonstration history has realistic dates.
     */
    private function at(Carbon $moment, callable $callback): void
    {
        $previous = Carbon::getTestNow();
        Carbon::setTestNow($moment);

        try {
            $callback();
        } finally {
            Carbon::setTestNow($previous);
        }
    }
}
