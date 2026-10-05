<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\VaccinationRequest;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Services\VaccinationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VaccinationController extends Controller
{
    public function create(Patient $patient): View
    {
        $this->authorize('recordVaccination', $patient);

        return view('clinical.vaccination', [
            'patient' => $patient,
            'vaccines' => Vaccine::query()->where('is_active', true)->orderBy('name')->get(),
            'given' => $patient->vaccinations()->with('vaccine')->latest('administered_on')->get(),
        ]);
    }

    public function store(VaccinationRequest $request, Patient $patient, VaccinationService $vaccinations): RedirectResponse
    {
        $this->authorize('recordVaccination', $patient);

        $data = $request->validated();

        $vaccination = $vaccinations->record($patient, Vaccine::findOrFail($data['vaccine_id']), $data, $request->user());

        $message = "Dose {$vaccination->dose_number} of {$vaccination->vaccine->name} recorded.";

        if ($vaccination->next_dose_due_on) {
            $message .= ' A reminder has been set for '.$vaccination->next_dose_due_on->format('j F Y').'.';
        }

        return redirect()->route('patients.show', $patient)->with('success', $message);
    }
}
