<?php

namespace App\Http\Controllers\Patients;

use App\Enums\PatientStatus;
use App\Enums\Sex;
use App\Http\Controllers\Controller;
use App\Http\Requests\PatientRequest;
use App\Models\District;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Services\AuditLogger;
use App\Services\PassportAccessService;
use App\Services\PatientRegistrationService;
use App\Services\QrCodeService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Issuing a passport, reading it and keeping the holder's details up to date.
 * There is no list of patients: a passport is found by scanning its card.
 */
class PatientController extends Controller
{
    public function __construct(
        private readonly PatientRegistrationService $registration,
        private readonly PassportAccessService $access,
        private readonly SettingService $settings,
        private readonly AuditLogger $audit,
    ) {
    }

    public function create(Request $request): View
    {
        $mother = null;

        if ($request->filled('mother')) {
            $mother = Patient::query()->findOrFail($request->integer('mother'));
            $this->authorize('view', $mother);
        }

        return view('patients.form', [...$this->formData(new Patient), 'mother' => $mother]);
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        $result = $this->registration->register(
            $request->patientData(),
            $request->contacts(),
            $request->user(),
            $request->boolean('create_portal_account'),
        );

        $patient = $result['patient'];

        $redirect = redirect()->route('patients.show', $patient)
            ->with('success', "{$patient->full_name} now has passport number {$patient->passport_number}.");

        if ($result['temporary_password']) {
            $redirect->with('temporary_password', [
                'name' => $patient->full_name,
                'email' => $patient->email,
                'password' => $result['temporary_password'],
            ]);
        }

        return $redirect;
    }

    /**
     * The passport. It opens only while the health worker holds an open scan.
     */
    public function show(Request $request, Patient $patient, QrCodeService $qr): View
    {
        $this->authorize('view', $patient);

        $patient->load(['district', 'registeredFacility', 'emergencyContacts', 'mother', 'children', 'portalAccount']);

        return view('patients.show', [
            'patient' => $patient,
            'qrCode' => $qr->forPatient($patient, 132),
            'access' => $this->access->activeFor($request->user(), $patient),
            'encounters' => $patient->encounters()->with(['facility', 'recordedBy', 'medications'])->limit(20)->get(),
            'vaccinations' => $patient->vaccinations()->with(['vaccine', 'facility'])->get(),
            'reminders' => $patient->reminders()->with('deliveries')->latest('due_on')->get(),
            'vaccines' => Vaccine::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Patient $patient): View
    {
        $this->authorize('update', $patient);

        $patient->load('emergencyContacts', 'mother');

        return view('patients.form', [...$this->formData($patient), 'mother' => $patient->mother]);
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        $this->authorize('update', $patient);

        $status = $request->validate(['status' => ['required', Rule::enum(PatientStatus::class)]])['status'];

        $patient->update([...$request->patientData(), 'status' => $status]);
        $this->registration->syncEmergencyContacts($patient, $request->contacts());

        $this->audit->record('patient.updated', "Updated the personal details of {$patient->full_name}.", $patient);

        return redirect()->route('patients.show', $patient)->with('success', 'The details have been saved.');
    }

    public function createPortalAccount(Patient $patient): RedirectResponse
    {
        $this->authorize('update', $patient);

        $password = $this->registration->createPortalAccount($patient);

        $this->audit->record('patient.portal-account-created', "Created a portal account for {$patient->full_name}.", $patient);

        return back()->with('success', 'The portal account has been created.')
            ->with('temporary_password', ['name' => $patient->full_name, 'email' => $patient->email, 'password' => $password]);
    }

    /**
     * Printable health passport card with the QR code.
     */
    public function card(Patient $patient, QrCodeService $qr): View
    {
        $this->authorize('printCard', $patient);

        return view('patients.card', [
            'patient' => $patient->load(['registeredFacility', 'emergencyContacts']),
            'qrCode' => $qr->forPatient($patient, 150),
        ]);
    }

    private function formData(Patient $patient): array
    {
        return [
            'patient' => $patient,
            'districts' => District::query()->orderBy('name')->get(),
            'sexes' => Sex::options(),
            'statuses' => PatientStatus::options(),
            'bloodGroups' => config('health_passport.blood_groups'),
            'relationships' => $this->settings->list('relationship_types'),
            'separationAge' => $this->settings->childSeparationAge(),
            'nationalIdLength' => $this->settings->nationalIdLength(),
        ];
    }
}
