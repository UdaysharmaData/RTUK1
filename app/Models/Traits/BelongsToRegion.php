<?php

namespace App\Models\Traits;

use App\Models\Region;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToRegion
{
    /**
     * Get the region.
     * 
     * @return BelongsTo
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
