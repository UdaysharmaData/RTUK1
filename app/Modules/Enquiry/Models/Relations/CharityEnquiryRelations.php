<?php

namespace App\Modules\Enquiry\Models\Relations;

use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\CharityCategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait CharityEnquiryRelations
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

    /**
     * Get the charity category.
     * @return BelongsTo
     */
    public function charityCategory(): BelongsTo
    {
        return $this->belongsTo(CharityCategory::class);
    }
}