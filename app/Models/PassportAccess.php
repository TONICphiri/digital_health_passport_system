<?php

namespace App\Models;

use App\Enums\AccessMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record that a health worker opened a passport, and for how long it
 * stays open. It is both the gate to the passport and the holder's access history.
 */
class PassportAccess extends Model
{
    protected $fillable = [
        'patient_id',
        'user_id',
        'facility_id',
        'method',
        'opened_at',
        'expires_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'method' => AccessMethod::class,
            'opened_at' => 'datetime',
            'expires_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null && $this->expires_at->isFuture();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('closed_at')->where('expires_at', '>', now());
    }
}
