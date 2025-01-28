<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\City;

trait HasManyCities
{
    /**
     * Get all of the cities for the model.
     * 
     * @return HasMany
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}