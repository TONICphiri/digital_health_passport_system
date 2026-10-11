<?php

namespace App\Models;

use App\Enums\VisitStageType;
use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A staged visit: reception, triage, consultation and dispensing. Each stage
 * is credited to the worker who recorded it and the visit locks once the
 * pharmacist has handed over every prescribed medicine.
 */
class Visit extends Model
{
    protected $fillable = [
        'patient_id',
        'facility_id',
        'reason',
        'status',
        'follow_up_on',
        'started_by',
        'started_at',
        'completed_by',
        'completed_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => VisitStatus::class,
            'follow_up_on' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'locked_at' => 'datetime',
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

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(VisitStage::class)->orderBy('recorded_at');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(VisitObservation::class)->orderBy('recorded_at');
    }

    public function medications(): HasMany
    {
        return $this->hasMany(VisitMedication::class);
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isCompleted(): bool
    {
        return $this->status === VisitStatus::Completed;
    }

    public function stage(VisitStageType $type): ?VisitStage
    {
        return $this->stages->firstWhere('type', $type);
    }

    /**
     * The newest reading of a vital sign, or null when it was never taken.
     */
    public function latestObservation(string $type): ?VisitObservation
    {
        return $this->observations
            ->where('type', $type)
            ->sortByDesc('recorded_at')
            ->first();
    }

    /**
     * The newest reading of each vital sign, keyed by type.
     *
     * @return \Illuminate\Support\Collection<string, VisitObservation>
     */
    public function latestObservations(): \Illuminate\Support\Collection
    {
        return $this->observations
            ->sortBy('recorded_at')
            ->keyBy('type');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('locked_at');
    }
}
