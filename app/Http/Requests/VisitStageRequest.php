<?php

namespace App\Http\Requests;

use App\Enums\VisitStageType;
use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates one stage of a visit. The fields depend on the stage: reception
 * takes the reason, triage the symptoms and any tests, consultation the
 * diagnosis and prescription. Vital signs may be sent with any of the three.
 */
class VisitStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function stage(): VisitStageType
    {
        return VisitStageType::tryFrom((string) $this->route('type')) ?? abort(404);
    }

    public function rules(): array
    {
        return [
            ...$this->vitalRules(),
            ...match ($this->stage()) {
                VisitStageType::Reception => [
                    'reason' => ['required', 'string', 'max:255'],
                ],
                VisitStageType::Triage => [
                    'symptoms' => ['required', 'string', 'max:2000'],
                    'tests' => ['nullable', 'array', 'max:15'],
                    'tests.*.name' => ['required_with:tests.*.result', 'nullable', 'string', 'max:150'],
                    'tests.*.result' => ['nullable', 'string', 'max:255'],
                ],
                VisitStageType::Consultation => [
                    'diagnosis' => ['required', 'string', 'max:255'],
                    'treatment_summary' => ['nullable', 'string', 'max:5000'],
                    'follow_up_on' => ['nullable', 'date', 'after:today'],
                    'medications' => ['nullable', 'array', 'max:'.config('health_passport.max_medications')],
                    'medications.*.name' => ['nullable', 'string', 'max:150'],
                    'medications.*.dosage' => ['required_with:medications.*.name', 'nullable', 'string', 'max:100'],
                    'medications.*.frequency' => ['required_with:medications.*.name', 'nullable', Rule::in(app(SettingService::class)->list('dosage_frequencies'))],
                    'medications.*.duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
                ],
                VisitStageType::Dispensing => [],
            },
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function vitalRules(): array
    {
        $limits = config('health_passport.vital_limits');
        $between = fn (string $key) => 'between:'.$limits[$key]['min'].','.$limits[$key]['max'];

        return [
            'temperature' => ['nullable', 'numeric', $between('temperature')],
            'weight' => ['nullable', 'numeric', $between('weight')],
            'height' => ['nullable', 'numeric', $between('height')],
            'systolic_pressure' => ['nullable', 'required_with:diastolic_pressure', 'integer', $between('systolic_pressure')],
            'diastolic_pressure' => ['nullable', 'required_with:systolic_pressure', 'integer', $between('diastolic_pressure'), 'lt:systolic_pressure'],
            'pulse_rate' => ['nullable', 'integer', $between('pulse_rate')],
            'oxygen_saturation' => ['nullable', 'integer', $between('oxygen_saturation')],
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => 'reason for the visit',
            'follow_up_on' => 'follow up date',
            'systolic_pressure' => 'systolic pressure',
            'diastolic_pressure' => 'diastolic pressure',
            'oxygen_saturation' => 'oxygen saturation',
        ];
    }

    public function messages(): array
    {
        return [
            'diastolic_pressure.lt' => 'The diastolic pressure must be lower than the systolic pressure.',
            'follow_up_on.after' => 'The follow up date must be after today.',
            'medications.*.dosage.required_with' => 'Enter the dosage for each medicine.',
            'medications.*.frequency.required_with' => 'Choose how often each medicine is taken.',
            'tests.*.name.required_with' => 'Enter the name of each test.',
        ];
    }
}
