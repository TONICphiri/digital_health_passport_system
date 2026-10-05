<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaccination extends Model
{
    protected $fillable = [
        'patient_id',
        'vaccine_id',
        'facility_id',
        'administered_by',
        'dose_number',
        'administered_on',
        'batch_number',
        'next_dose_due_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'administered_on' => 'date',
            'next_dose_due_on' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }
}
