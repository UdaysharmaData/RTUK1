<?php

namespace App\Modules\Event\Models\Relations;

use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventThirdParty;
use App\Modules\Enquiry\Models\ExternalEnquiry;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait EventCategoryEventThirdPartyRelations
{
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
     * Get the event third party.
     * 
     * @return BelongsTo
     */
    public function eventThirdParty(): BelongsTo
    {
        return $this->belongsTo(EventThirdParty::class);
    }

    /**
     * Get the external enquiries associated with this event category event thirt party.
     * 
     * @return HasMany
     */
    public function externalEnquiries(): HasMany
    {
        return $this->hasMany(ExternalEnquiry::class);
    }
}