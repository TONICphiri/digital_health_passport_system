<?php

namespace App\Http\Controllers\Patients;

use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\EncounterRequest;
use App\Http\Requests\VaccinationRequest;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Services\EncounterService;
use App\Services\PassportAccessService;
use App\Services\VaccinationService;
use App\Support\OfflineForms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;
use Illuminate\View\View;

/**
 * Visits typed on a device without a connection. The device keeps the scanned
 * card and the notes. Once the server can be reached they arrive here, and the
 * passport is opened first, so every rule of the normal workflow still applies
 * and the opening is recorded in the passport's access log.
 */
class OfflineVisitController extends Controller
{
    public function __construct(private readonly PassportAccessService $access)
    {
    }

    /**
     * A plain page that can be stored on the device and shown without a connection.
     * It holds no patient or account information.
     */
    public function shell(): View
    {
        return view('offline.scan', OfflineForms::data());
    }

    public function store(Request $request, EncounterService $encounters, VaccinationService $vaccinations): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:120'],
            'sync_id' => ['required', 'uuid'],
            'draft' => ['nullable', 'array'],
            'draft.encounter' => ['nullable', 'array'],
            'draft.vaccination' => ['nullable', 'array'],
        ]);

        $worker = $request->user();
        $key = "offline-sync:{$worker->id}:{$data['sync_id']}";

        // The same visit may arrive twice if the first reply was lost on the way back.
        if (Cache::has($key)) {
            return redirect()->route('patients.scan')->with('success', 'That visit has already been sent.');
        }

        $patient = $this->access->openByScan($data['code'], $worker)->patient;

        $done = [];
        $problems = [];
        $invalid = null;

        if (! empty($data['draft']['encounter'])) {
            $form = new EncounterRequest;
            $validator = Validator::make($data['draft']['encounter'], $form->rules(), $form->messages(), $form->attributes());

            if ($validator->fails()) {
                $invalid ??= ['route' => 'encounters.create', 'validator' => $validator, 'input' => $data['draft']['encounter']];
            } elseif (! Gate::forUser($worker)->allows('record', $patient)) {
                $problems[] = 'The visit notes were not saved because this passport cannot take new entries.';
            } else {
                $values = $validator->validated();
                $medications = collect($values['medications'] ?? [])->filter(fn (array $row) => ! empty($row['name']))->values()->all();
                $encounters->record($patient, collect($values)->except('medications')->all(), $medications, $worker);
                $done[] = 'The visit notes were added.';
            }
        }

        if (! empty($data['draft']['vaccination'])) {
            $form = new VaccinationRequest;
            $validator = Validator::make($data['draft']['vaccination'], $form->rules(), [], $form->attributes());

            if ($validator->fails()) {
                $invalid ??= ['route' => 'vaccinations.create', 'validator' => $validator, 'input' => $data['draft']['vaccination']];
            } elseif (! Gate::forUser($worker)->allows('recordVaccination', $patient)) {
                $problems[] = 'The vaccination was not saved because this passport cannot take new entries.';
            } else {
                try {
                    $values = $validator->validated();
                    $vaccination = $vaccinations->record($patient, Vaccine::findOrFail($values['vaccine_id']), $values, $worker);
                    $done[] = "Dose {$vaccination->dose_number} of {$vaccination->vaccine->name} was recorded.";
                } catch (WorkflowException $exception) {
                    $problems[] = $exception->getMessage();
                }
            }
        }

        Cache::put($key, true, now()->addDays(7));

        // "synced" tells the device the server has taken over, so it can forget its copy.
        $target = ['patient' => $patient, 'synced' => $data['sync_id']];

        if ($invalid) {
            return redirect()->route($invalid['route'], $target)
                ->withErrors($invalid['validator'])
                ->withInput($invalid['input'])
                ->with('error', trim(implode(' ', [...$done, ...$problems, 'Some details need correcting before they can be saved.'])));
        }

        $redirect = redirect()->route('patients.show', $target);

        if ($done) {
            $redirect->with('success', implode(' ', $done));
        }

        return $problems ? $redirect->with('error', implode(' ', $problems)) : $redirect;
    }
}
