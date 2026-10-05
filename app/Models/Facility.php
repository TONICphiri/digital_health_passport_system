<?php

namespace App\Models;

use App\Enums\FacilityStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'ownership',
        'district_id',
        'physical_address',
        'phone',
        'email',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => FacilityStatus::class,
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'registered_facility_id');
    }

    public function isActive(): bool
    {
        return $this->status === FacilityStatus::Active;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', FacilityStatus::Active);
    }
}
