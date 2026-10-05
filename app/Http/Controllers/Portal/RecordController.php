<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ReminderStatus;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The patient's own health records, and those of children linked to them.
 */
class RecordController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $this->owner($request);
        $patient = $this->selectedPatient($request, $owner);

        return view('portal.records', [
            'owner' => $owner,
            'patient' => $patient->load(['emergencyContacts', 'registeredFacility']),
            'family' => collect([$owner])->merge($owner->children),
            'encounters' => $patient->encounters()->with(['facility', 'recordedBy', 'medications'])->get(),
            'vaccinations' => $patient->vaccinations()->with(['vaccine', 'facility'])->latest('administered_on')->get(),
            'accesses' => $patient->accesses()->with(['user', 'facility'])->limit(5)->get(),
            'reminders' => $patient->reminders()->where('status', ReminderStatus::Active)->orderBy('due_on')->get(),
        ]);
    }

    public function card(Request $request, QrCodeService $qr): View
    {
        $owner = $this->owner($request);
        $patient = $this->selectedPatient($request, $owner);

        return view('patients.card', [
            'patient' => $patient->load(['registeredFacility', 'emergencyContacts']),
            'qrCode' => $qr->forPatient($patient, 150),
        ]);
    }

    /**
     * Every time a health worker opened this passport, newest first.
     */
    public function accessLog(Request $request): View
    {
        $owner = $this->owner($request);
        $patient = $this->selectedPatient($request, $owner);

        return view('portal.access-log', [
            'owner' => $owner,
            'patient' => $patient,
            'family' => collect([$owner])->merge($owner->children),
            'accesses' => $patient->accesses()->with(['user', 'facility'])->paginate($this->perPage())->withQueryString(),
        ]);
    }

    /**
     * Printable summary the holder can keep or show: personal facts, emergency
     * contacts and the vaccination history. Visit notes are not included.
     */
    public function summary(Request $request): View
    {
        $owner = $this->owner($request);
        $patient = $this->selectedPatient($request, $owner);

        return view('portal.summary', [
            'patient' => $patient->load(['emergencyContacts', 'registeredFacility']),
            'vaccinations' => $patient->vaccinations()->with(['vaccine', 'facility'])->orderBy('administered_on')->get(),
        ]);
    }

    private function owner(Request $request): Patient
    {
        $patient = $request->user()->patient;
        abort_if(! $patient, 403, 'This account is not linked to a health passport.');

        return $patient->load('children');
    }

    /**
     * A mother may switch to the record of a linked child.
     */
    private function selectedPatient(Request $request, Patient $owner): Patient
    {
        if (! $request->filled('patient')) {
            return $owner;
        }

        $child = $owner->children->firstWhere('id', $request->integer('patient'));
        abort_if(! $child, 403, 'You can only view your own record and the records of your children.');

        return $child;
    }
}
