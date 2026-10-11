<?php

namespace App\Models;

use App\Enums\VisitStageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stage of a visit. The first recorder is kept; an edit records who
 * changed it and when.
 */
class VisitStage extends Model
{
    protected $fillable = [
        'visit_id',
        'type',
        'recorded_by',
        'recorded_as_position',
        'summary',
        'payload',
        'recorded_at',
        'edited_by',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => VisitStageType::class,
            'payload' => 'array',
            'recorded_at' => 'datetime',
            'edited_at' => 'datetime',
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

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * A single value from the stage payload.
     */
    public function data(string $key, mixed $default = null): mixed
    {
        return data_get($this->payload, $key, $default);
    }
}
