<?php

namespace App\Http\Controllers\Patients;

use App\Enums\VisitStageType;
use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\VaccinationRequest;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Services\PassportAccessService;
use App\Services\VaccinationService;
use App\Services\VisitService;
use App\Support\OfflineForms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Notes typed on a device without a connection. The device keeps the scanned
 * card and the notes. Once the server can be reached they arrive here, and the
 * passport is opened first, so every rule of the normal workflow still applies
 * and the opening is recorded in the passport's access log.
 *
 * Only the front desk note and a vaccination can be typed offline. The stages
 * that need a position at the facility are recorded while connected.
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

    public function store(Request $request, VaccinationService $vaccinations, VisitService $visits): RedirectResponse
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
            $validator = Validator::make($data['draft']['encounter'], $this->receptionRules(), [], $this->receptionAttributes());

            if ($validator->fails()) {
                $invalid ??= ['route' => 'visits.stage.create', 'params' => ['type' => VisitStageType::Reception->value], 'validator' => $validator, 'input' => $data['draft']['encounter']];
            } else {
                $visit = $visits->openOrDraft($patient, $worker);

                if (! Gate::forUser($worker)->allows('recordStage', [$visit, VisitStageType::Reception->value])) {
                    $problems[] = 'The front desk note was not saved because this passport cannot take new entries.';
                } else {
                    $visits->saveStage($patient, $visit, VisitStageType::Reception, $validator->validated(), $worker);
                    $done[] = 'The front desk note was added.';
                }
            }
        }

        if (! empty($data['draft']['vaccination'])) {
            $form = new VaccinationRequest;
            $validator = Validator::make($data['draft']['vaccination'], $form->rules(), [], $form->attributes());

            if ($validator->fails()) {
                $invalid ??= ['route' => 'vaccinations.create', 'params' => [], 'validator' => $validator, 'input' => $data['draft']['vaccination']];
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
            return redirect()->route($invalid['route'], [...$target, ...$invalid['params']])
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

    /**
     * The front desk note is the reason for the visit and any readings taken
     * at the desk. The other stages need the worker's position and a connection.
     *
     * @return array<string, array<int, mixed>>
     */
    private function receptionRules(): array
    {
        $limits = config('health_passport.vital_limits');
        $between = fn (string $key) => 'between:'.$limits[$key]['min'].','.$limits[$key]['max'];

        return [
            'reason' => ['required', 'string', 'max:255'],
            'temperature' => ['nullable', 'numeric', $between('temperature')],
            'weight' => ['nullable', 'numeric', $between('weight')],
            'height' => ['nullable', 'numeric', $between('height')],
            'systolic_pressure' => ['nullable', 'required_with:diastolic_pressure', 'integer', $between('systolic_pressure')],
            'diastolic_pressure' => ['nullable', 'required_with:systolic_pressure', 'integer', $between('diastolic_pressure'), 'lt:systolic_pressure'],
            'pulse_rate' => ['nullable', 'integer', $between('pulse_rate')],
            'oxygen_saturation' => ['nullable', 'integer', $between('oxygen_saturation')],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function receptionAttributes(): array
    {
        return [
            'reason' => 'reason for the visit',
            'systolic_pressure' => 'systolic pressure',
            'diastolic_pressure' => 'diastolic pressure',
            'oxygen_saturation' => 'oxygen saturation',
        ];
    }
}
