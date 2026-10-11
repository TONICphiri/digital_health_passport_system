<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One vital sign reading. Readings are added, never overwritten, so a
 * correction keeps the original and credits the person who changed it.
 */
class VisitObservation extends Model
{
    protected $fillable = [
        'visit_id',
        'type',
        'value',
        'recorded_by',
        'recorded_as_position',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function label(): string
    {
        return config("health_passport.observation_types.{$this->type}.label", ucfirst($this->type));
    }

    public function unit(): ?string
    {
        return config("health_passport.observation_types.{$this->type}.unit");
    }
}
