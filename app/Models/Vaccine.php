<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Vaccine extends Model
{
    /**
     * The active vaccine list is read on most clinical pages but changes
     * rarely, so it is cached until an administrator edits a vaccine.
     */
    public const ACTIVE_CACHE_KEY = 'vaccines.active';

    protected $fillable = ['name', 'protects_against', 'total_doses', 'days_between_doses', 'recommended_age', 'recommended_age_days', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'recommended_age_days' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::ACTIVE_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::ACTIVE_CACHE_KEY));
    }

    /**
     * @return Collection<int, Vaccine>
     */
    public static function activeCached(): Collection
    {
        return Cache::rememberForever(self::ACTIVE_CACHE_KEY, fn () => static::query()->active()->orderBy('name')->get());
    }

    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
