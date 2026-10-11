<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vaccine extends Model
{
    protected $fillable = ['name', 'protects_against', 'total_doses', 'days_between_doses', 'recommended_age', 'recommended_age_days', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'recommended_age_days' => 'integer'];
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
