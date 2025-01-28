<?php

namespace App\Models\Relations;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Modules\Setting\Models\Site;


trait MedalRelations
{

    /**
     * Get the site associated with the region
     *
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * get the medalable associated with the medal model
     * @return MorphTo
     */
    public function medalable(): MorphTo
    {
        return $this->morphTo();
    }
}
