<?php

namespace App\Modules\Charity\Models\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\ResaleRequest;
use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

trait ResalePlaceRelations
{
    use BelongsToEventTrait;

    /**
     * Get the charity that owns the resale place.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }

    /**
     * Get the resale requests associated with the resale place.
     * @return HasMany
     */
    public function resaleRequests(): HasMany
    {
        return $this->hasMany(ResaleRequest::class);
    }
}