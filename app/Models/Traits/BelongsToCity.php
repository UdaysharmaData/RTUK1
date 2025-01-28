<?php

namespace App\Models\Traits;

use App\Models\City;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCity
{
    /**
     * Get the city.
     * 
     * @return BelongsTo
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
