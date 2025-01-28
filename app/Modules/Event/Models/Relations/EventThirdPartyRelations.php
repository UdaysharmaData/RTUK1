<?php

namespace App\Modules\Event\Models\Relations;

use App\Modules\Event\Models\EventCategory;
use App\Modules\Partner\Models\PartnerChannel;
use App\Modules\Event\Models\EventCategoryEventThirdParty;
use App\Modules\Event\Models\Traits\BelongsTo\BelongsToEventTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait EventThirdPartyRelations
{
    use BelongsToEventTrait;

    /**
     * Get the partner channel.
     * 
     * @return BelongsTo
     */
    public function partnerChannel(): BelongsTo
    {
        return $this->belongsTo(PartnerChannel::class);
    }

    /**
     * Get the event categories associated with the event third party.
     * 
     * @return BelongsToMany
     */
    public function eventCategories(): BelongsToMany
    {
        return $this->belongsToMany(EventCategory::class, 'event_category_event_third_party', 'event_third_party_id', 'event_category_id')->using(EventCategoryEventThirdParty::class)->withPivot('id', 'ref', 'external_id', 'created_at', 'updated_at');
    }
}