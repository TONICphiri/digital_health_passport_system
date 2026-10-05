<?php

namespace App\Http\Requests;

use App\Enums\Sex;
use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $patient = $this->route('patient');
        $settings = app(SettingService::class);

        return [
            'is_child' => ['sometimes', 'boolean'],
            'national_id' => ['nullable', 'required_if:is_child,0', 'string', 'size:'.$settings->nationalIdLength(), 'alpha_num', Rule::unique('patients')->ignore($patient)],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'sex' => ['required', Rule::enum(Sex::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('patients')->ignore($patient)],
            'sms_consent' => ['nullable', 'boolean'],
            'district_id' => ['required', 'exists:districts,id'],
            'traditional_authority' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'physical_address' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'blood_group' => ['nullable', Rule::in(config('health_passport.blood_groups'))],
            'allergies' => ['nullable', 'string', 'max:1000'],
            'chronic_conditions' => ['nullable', 'string', 'max:1000'],
            'disabilities' => ['nullable', 'string', 'max:1000'],
            'health_notes' => ['nullable', 'string', 'max:2000'],
            'mother_id' => ['nullable', 'required_if:is_child,1', 'exists:patients,id'],

            'contacts' => ['array', 'max:3'],
            'contacts.0.full_name' => ['required', 'string', 'max:150'],
            'contacts.0.phone' => ['required', 'string', 'max:30'],
            'contacts.*.full_name' => ['nullable', 'string', 'max:150'],
            'contacts.*.relationship' => ['nullable', 'required_with:contacts.*.full_name', Rule::in($settings->list('relationship_types'))],
            'contacts.*.phone' => ['nullable', 'required_with:contacts.*.full_name', 'string', 'max:30'],
            'contacts.*.physical_address' => ['nullable', 'string', 'max:255'],

            'create_portal_account' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'national_id.required_if' => 'Enter the National ID.',
            'mother_id.required_if' => 'Scan the mother\'s passport to link the child.',
            'national_id.size' => 'The National ID must be exactly '.app(SettingService::class)->nationalIdLength().' characters.',
            'national_id.unique' => 'A passport already exists for this National ID. Scan the card to open it.',
            'email.unique' => 'This email address is already used by another passport holder.',
            'contacts.0.full_name.required' => 'Enter at least one emergency contact.',
            'contacts.0.phone.required' => 'Enter the phone number of the emergency contact.',
            'contacts.*.relationship.required_with' => 'Choose the relationship of each emergency contact.',
            'contacts.*.phone.required_with' => 'Enter a phone number for each emergency contact.',
        ];
    }

    public function attributes(): array
    {
        return ['district_id' => 'district', 'mother_id' => 'mother'];
    }

    /**
     * @return array<string, mixed>
     */
    public function patientData(): array
    {
        return $this->safe()->except(['contacts', 'create_portal_account', 'is_child']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function contacts(): array
    {
        return $this->validated('contacts', []);
    }
}
