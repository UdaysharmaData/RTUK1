<?php

namespace App\Modules\Event\Models\Relations;

use App\Modules\Event\Models\EventCategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait NationalAverageRelations
{
    /**
     * Get the event category.
     * 
     * @return BelongsTo
     */
    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }
}
