<?php

namespace App\Models;

use App\Enums\ReminderCategory;
use App\Enums\ReminderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reminder extends Model
{
    protected $fillable = [
        'patient_id',
        'category',
        'title',
        'message',
        'due_on',
        'repeat_every_days',
        'is_confidential',
        'status',
        'last_sent_at',
        'source_type',
        'source_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => ReminderCategory::class,
            'status' => ReminderStatus::class,
            'due_on' => 'date',
            'is_confidential' => 'boolean',
            'last_sent_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ReminderDelivery::class);
    }

    /**
     * What happened the last time this reminder was sent.
     *
     * @return \Illuminate\Support\Collection<int, ReminderDelivery>
     */
    public function lastDeliveries()
    {
        $latest = $this->deliveries->sortByDesc('id');
        $batch = $latest->first()?->batch;

        return $latest->where('batch', $batch)->sortBy('id')->values();
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', ReminderStatus::Active)
            ->whereDate('due_on', '<=', today())
            ->where(function (Builder $inner) {
                $inner->whereNull('last_sent_at')->orWhereDate('last_sent_at', '<', today());
            });
    }
}
