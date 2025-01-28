<?php

namespace App\Modules\Enquiry\Models\Relations;

use App\Modules\Setting\Models\Site;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait EventEnquiryRelations
{
    /**
     * Get the site that owns the enquiry.
     * 
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}