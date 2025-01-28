<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\Venue;

trait HasManyVenues
{
    /**
     * Get all of the venues for the model.
     * 
     * @return HasManyany
     */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }
}