<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VaccinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vaccine_id' => ['required', Rule::exists('vaccines', 'id')->where('is_active', true)],
            'administered_on' => ['required', 'date', 'before_or_equal:today'],
            'batch_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['vaccine_id' => 'vaccine', 'administered_on' => 'date given'];
    }
}
