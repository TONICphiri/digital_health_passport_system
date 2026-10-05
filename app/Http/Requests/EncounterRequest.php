<?php

namespace App\Http\Requests;

use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $limits = config('health_passport.vital_limits');
        $between = fn (string $key) => 'between:'.$limits[$key]['min'].','.$limits[$key]['max'];

        return [
            'reason' => ['required', 'string', 'max:255'],
            'diagnosis' => ['required', 'string', 'max:255'],
            'treatment_summary' => ['nullable', 'string', 'max:5000'],
            'follow_up_on' => ['nullable', 'date', 'after:today'],
            'temperature' => ['nullable', 'numeric', $between('temperature')],
            'weight' => ['nullable', 'numeric', $between('weight')],
            'height' => ['nullable', 'numeric', $between('height')],
            'systolic_pressure' => ['nullable', 'required_with:diastolic_pressure', 'integer', $between('systolic_pressure')],
            'diastolic_pressure' => ['nullable', 'required_with:systolic_pressure', 'integer', $between('diastolic_pressure'), 'lt:systolic_pressure'],
            'pulse_rate' => ['nullable', 'integer', $between('pulse_rate')],
            'oxygen_saturation' => ['nullable', 'integer', $between('oxygen_saturation')],
            'medications' => ['nullable', 'array', 'max:'.config('health_passport.max_medications')],
            'medications.*.name' => ['nullable', 'string', 'max:150'],
            'medications.*.dosage' => ['required_with:medications.*.name', 'nullable', 'string', 'max:100'],
            'medications.*.frequency' => ['required_with:medications.*.name', 'nullable', Rule::in(app(SettingService::class)->list('dosage_frequencies'))],
            'medications.*.duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => 'reason for the visit',
            'follow_up_on' => 'follow up date',
            'temperature' => 'temperature',
            'systolic_pressure' => 'systolic pressure',
            'diastolic_pressure' => 'diastolic pressure',
            'oxygen_saturation' => 'oxygen saturation',
        ];
    }

    public function messages(): array
    {
        return [
            'diastolic_pressure.lt' => 'The diastolic pressure must be lower than the systolic pressure.',
            'medications.*.dosage.required_with' => 'Enter the dosage for each medicine.',
            'medications.*.frequency.required_with' => 'Choose how often each medicine is taken.',
            'follow_up_on.after' => 'The follow up date must be after today.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function encounterData(): array
    {
        return collect($this->validated())->except('medications')->all();
    }

    /**
     * Rows left empty are removed.
     *
     * @return array<int, array<string, mixed>>
     */
    public function medicationRows(): array
    {
        return collect($this->validated('medications', []))
            ->filter(fn (array $row) => ! empty($row['name']))
            ->values()
            ->all();
    }
}
