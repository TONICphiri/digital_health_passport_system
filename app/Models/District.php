<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = ['name', 'region'];

    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }
}
