<?php

namespace App\Traits;

use App\Modules\Event\Models\PromotionalFeaturedEvent;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasManyPromotionalFeaturedEvents
{
    /**
     * Get the promotional featured events associated with the region
     *
     * @return HasMany
     */
    public function promotionalFeaturedEvents(): HasMany
    {
        return $this->hasMany(PromotionalFeaturedEvent::class);
    }
}
