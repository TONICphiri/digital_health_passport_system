<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A medicine prescribed during consultation and marked handed over by the
 * pharmacist. The system only records the hand over; it does not stock or
 * dispense anything.
 */
class VisitMedication extends Model
{
    protected $fillable = [
        'visit_id',
        'name',
        'dosage',
        'frequency',
        'duration_days',
        'prescribed_by',
        'prescribed_at',
        'dispensed',
        'dispensed_by',
        'dispensed_at',
    ];

    protected function casts(): array
    {
        return [
            'dispensed' => 'boolean',
            'prescribed_at' => 'datetime',
            'dispensed_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function prescribedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescribed_by');
    }

    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
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
