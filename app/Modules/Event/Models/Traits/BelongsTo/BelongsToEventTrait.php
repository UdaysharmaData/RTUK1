<?php

namespace App\Modules\Event\Models\Traits\BelongsTo;

use App\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToEventTrait
{
    /**
     * Get the event
     * 
     * @return BelongsTo
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class)->withDrafted()->withTrashed();
    }
}
