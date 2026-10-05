<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One entry in a patient's passport, added when a health worker sees them.
 */
class Encounter extends Model
{
    protected $fillable = [
        'patient_id',
        'facility_id',
        'recorded_by',
        'reason',
        'diagnosis',
        'treatment_summary',
        'follow_up_on',
        'temperature',
        'weight',
        'height',
        'systolic_pressure',
        'diastolic_pressure',
        'pulse_rate',
        'oxygen_saturation',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_on' => 'date',
            'recorded_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function medications(): HasMany
    {
        return $this->hasMany(EncounterMedication::class);
    }

    public function bloodPressure(): ?string
    {
        return $this->systolic_pressure && $this->diastolic_pressure
            ? "{$this->systolic_pressure}/{$this->diastolic_pressure} mmHg"
            : null;
    }
}
