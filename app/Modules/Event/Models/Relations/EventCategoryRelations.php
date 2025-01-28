<?php

namespace App\Modules\Event\Models\Relations;

use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Event\Models\NationalAverage;
use App\Modules\Event\Models\EventThirdParty;
use App\Modules\Event\Models\EventPageListing;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Event\Models\EventCategoryEventThirdParty;
use App\Modules\Event\Models\EventCategoryEventPageListing;
use App\Modules\Event\Models\EventCategoryPromotionalEvent;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

trait EventCategoryRelations
{
    /**
     * Get the event that belong to the event category.
     * @return BelongsToMany
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_event_category', 'event_category_id', 'event_id')->using(EventEventCategory::class)->withPivot('id', 'ref', 'local_fee', 'international_fee', 'start_date', 'end_date', 'registration_deadline', 'withdrawal_deadline', 'total_places', 'classic_membership_places', 'premium_membership_places', 'two_year_membership_places')->withTimestamps();
    }

    /**
     * Get the eventEventCategory that belong to the event category.
     * This is mostly used for queries where the event_event_category_id is used like for the Participant model case.
     *
     * @return HasMany
     */
    public function eventEventCategories(): HasMany
    {
        return $this->hasMany(EventEventCategory::class);
    }

    /**
     * Get all of the participants registered to events belonging to the event category.
     */
    public function participants(): HasManyThrough
    {
        return $this->hasManyThrough(Participant::class, EventEventCategory::class, 'event_category_id', 'event_event_category_id');
    }

    /**
     * Get the site associated to the event category.
     *
     * @return BelongsTo
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the national averages that belong to the event category.
     *
     * @return HasMany
     */
    public function nationalAverages(): HasMany
    {
        return $this->hasMany(NationalAverage::class);
    }

    /**
     * Get the event page listings associated to the event category.
     *
     * @return BelongsToMany
     */
    public function eventPageListings(): BelongsToMany
    {
        return $this->belongsToMany(EventPageListing::class, 'event_category_event_page_listing', 'event_category_id', 'event_page_listing_id')->using(EventCategoryEventPageListing::class)->withPivot('id', 'priority', 'created_at', 'updated_at');
    }

    /**
     * Get the promotional events associated with the event category.
     *
     * @return BelongsToMany
     */
    public function promotionalEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_category_promotional_event', 'event_category_id', 'event_id')->using(EventCategoryPromotionalEvent::class)->withPivot('id', 'created_at', 'updated_at');
    }

    /**
     * Get the event third parties associated with the event category.
     *
     * @return BelongsToMany
     */
    public function eventThirdParties(): BelongsToMany
    {
        return $this->belongsToMany(EventThirdParty::class, 'event_category_event_third_party', 'event_category_id', 'event_third_party_id')->using(EventCategoryEventThirdParty::class)->withPivot('id', 'external_id', 'created_at', 'updated_at');
    }

    /**
     * Get the enquiries that belong to the event category.
     *
     * @return HasMany
     */
    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }
}
