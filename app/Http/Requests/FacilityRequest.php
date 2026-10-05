<?php

namespace App\Http\Requests;

use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = app(SettingService::class);
        $facility = $this->route('facility');

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('facilities')->ignore($facility)],
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('facilities')->ignore($facility)],
            'type' => ['required', Rule::in($settings->list('facility_types'))],
            'ownership' => ['required', Rule::in($settings->list('ownership_types'))],
            'district_id' => ['required', 'exists:districts,id'],
            'physical_address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'facility code', 'district_id' => 'district'];
    }
}
