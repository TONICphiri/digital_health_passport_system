<?php

namespace App\Http\Requests;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used when a System Administrator creates a Facility Administrator and when a
 * Facility Administrator creates Health Workers. The role is fixed by the
 * page used, so it is never taken from the form.
 */
class StaffAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $forFacilityStaff = $this->routeIs('facility.*');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'professional_registration_number' => ['nullable', 'string', 'max:50'],
            'facility_id' => [$forFacilityStaff ? 'prohibited' : 'required', 'exists:facilities,id'],
            'role' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [
            'facility_id' => 'facility',
            'professional_registration_number' => 'professional registration number',
        ];
    }

    public function accountData(): array
    {
        return $this->safe()->all();
    }
}
