<?php

namespace App\Modules\Partner\Models\Relations;

use App\Modules\Partner\Models\Partner;
use App\Modules\Event\Models\EventThirdParty;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait PartnerChannelRelations
{
    /**
     * Get the partner associated with the channel.
     * 
     * @return BelongsTo
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * The event third parties associated with the partner channel.
     * 
     * @return HasMany
     */
    public function eventThirdParties(): HasMany
    {
        return $this->hasMany(EventThirdParty::class);
    }

    /**
     * Get the external enquiries associated with the partner channel.
     * 
     * @return HasMany
     */
    public function externalEnquiries(): HasMany
    {
        return $this->hasMany(ExternalEnquiry::class);
    }
}