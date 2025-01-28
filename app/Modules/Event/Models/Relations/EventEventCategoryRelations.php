<?php

namespace App\Modules\Event\Models\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Participant\Models\Participant;

use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

trait EventEventCategoryRelations
{
    use BelongsToEventTrait;

    /**
     * Get the event category.
     * 
     * @return BelongsTo
     */
    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    /**
     * Get the participants associated to the event event category.
     * 
     * @return HasMany
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'event_event_category_id');
    }
}