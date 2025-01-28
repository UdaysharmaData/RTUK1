<?php

namespace App\Modules\Charity\Models\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\ResalePlace;

trait ResaleRequestRelations
{
    /**
     * The resale place
     * @return BelongsTo
     */
    public function resalePlace(): BelongsTo
    {
        return $this->belongsTo(ResalePlace::class);
    }

    /**
     * The charity that wants to purchase the event places
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
    }
}