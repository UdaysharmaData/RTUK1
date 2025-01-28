<?php

namespace App\Models\Traits;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToVenue
{
    /**
     * Get the venue.
     * 
     * @return BelongsTo
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
