<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\EncounterRequest;
use App\Models\Patient;
use App\Services\EncounterService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Adds an entry to an open passport.
 */
class EncounterController extends Controller
{
    public function create(Patient $patient, SettingService $settings): View
    {
        $this->authorize('record', $patient);

        return view('clinical.encounter', [
            'patient' => $patient,
            'frequencies' => $settings->list('dosage_frequencies'),
            'limits' => config('health_passport.vital_limits'),
            'maxMedications' => config('health_passport.max_medications'),
        ]);
    }

    public function store(EncounterRequest $request, Patient $patient, EncounterService $encounters): RedirectResponse
    {
        $this->authorize('record', $patient);

        $encounters->record($patient, $request->encounterData(), $request->medicationRows(), $request->user());

        return redirect()->route('patients.show', $patient)->with('success', 'The encounter has been added to the passport.');
    }
}
