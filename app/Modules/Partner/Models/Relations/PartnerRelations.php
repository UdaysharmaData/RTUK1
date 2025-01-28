<?php

namespace App\Modules\Partner\Models\Relations;

use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\PartnerPackage;
use App\Modules\Partner\Models\PartnerChannel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait PartnerRelations
{
    /**
     * The packages that belong to the partner.
     * 
     * @return HasMany
     */
    public function partnerPackages(): HasMany
    {
        return $this->hasMany(PartnerPackage::class);
    }

    /**
     * Get the site associated with the partner.
     * 
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * The channels that belong to the partner.
     * 
     * @return HasMany
     */
    public function partnerChannels(): HasMany
    {
        return $this->hasMany(PartnerChannel::class);
    }
}