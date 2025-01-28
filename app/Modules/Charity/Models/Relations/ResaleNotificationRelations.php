<?php

namespace App\Modules\Charity\Models\Relations;

use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;

trait ResaleNotificationRelations
{
    use BelongsToEventTrait;

    /**
     * Get the charity that wants to get notified whenever some places of the event are put on sale.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }
}