<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncounterMedication extends Model
{
    protected $fillable = ['encounter_id', 'name', 'dosage', 'frequency', 'duration_days'];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function directions(): string
    {
        return collect([
            $this->dosage,
            $this->frequency,
            $this->duration_days ? "for {$this->duration_days} ".($this->duration_days === 1 ? 'day' : 'days') : null,
        ])->filter()->implode(', ');
    }
}
