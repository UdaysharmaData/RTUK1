<?php

namespace App\Traits;

use App\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasManyEvents
{
    /**
     * Get the events associated with the region
     *
     * @return HasMany
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
