<?php

namespace App\Http\Controllers\Clinical;

use App\Enums\VisitStageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisitStageRequest;
use App\Models\Patient;
use App\Models\Visit;
use App\Services\SettingService;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Records the stages of a staged visit. Every action checks the VisitPolicy,
 * which confirms the worker holds the stage's position and has an open
 * passport, so a stage cannot be recorded by posting the form directly.
 */
class VisitController extends Controller
{
    public function __construct(private readonly VisitService $visits)
    {
    }

    public function createStage(Request $request, Patient $patient, string $type, SettingService $settings): View
    {
        $this->authorize('view', $patient);

        $stage = VisitStageType::tryFrom($type) ?? abort(404);
        $visit = $this->visits->openOrDraft($patient, $request->user());

        $this->authorize('recordStage', [$visit, $stage->value]);

        return view('clinical.visit-stage', [
            'patient' => $patient,
            'visit' => $visit,
            'stage' => $stage,
            'stageRecord' => $visit->exists ? $visit->stage($stage) : null,
            'vitals' => $visit->exists ? $visit->latestObservations() : collect(),
            'observationTypes' => config('health_passport.observation_types'),
            'limits' => config('health_passport.vital_limits'),
            'frequencies' => $settings->list('dosage_frequencies'),
            'maxMedications' => config('health_passport.max_medications'),
        ]);
    }

    public function storeStage(VisitStageRequest $request, Patient $patient, string $type): RedirectResponse
    {
        $this->authorize('view', $patient);

        $stage = VisitStageType::tryFrom($type) ?? abort(404);
        $visit = $this->visits->openOrDraft($patient, $request->user());

        $this->authorize('recordStage', [$visit, $stage->value]);

        $this->visits->saveStage($patient, $visit, $stage, $request->validated(), $request->user());

        return redirect()->route('patients.show', $patient)
            ->with('success', "The {$stage->label()} stage has been recorded.");
    }

    public function dispense(Patient $patient, Visit $visit, Request $request): View
    {
        $this->authorize('view', $patient);
        $this->ensureBelongsTo($patient, $visit);
        $this->authorize('dispense', $visit);

        return view('clinical.dispense', [
            'patient' => $patient,
            'visit' => $visit,
        ]);
    }

    public function storeDispense(Request $request, Patient $patient, Visit $visit): RedirectResponse
    {
        $this->authorize('view', $patient);
        $this->ensureBelongsTo($patient, $visit);
        $this->authorize('dispense', $visit);

        $data = $request->validate([
            'medications' => ['required', 'array'],
            'medications.*' => ['integer'],
        ], [], ['medications' => 'medicines']);

        $this->visits->dispense($visit, $data['medications'], $request->user());

        return redirect()->route('patients.show', $patient)
            ->with('success', $visit->refresh()->isCompleted()
                ? 'Every medicine has been handed over and the visit is complete.'
                : 'The chosen medicines have been marked as handed over.');
    }

    private function ensureBelongsTo(Patient $patient, Visit $visit): void
    {
        abort_unless($visit->patient_id === $patient->id, 404);
    }
}
