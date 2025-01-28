<?php

namespace App\Modules\Event\Models\Relations;

use App\Traits\SiteTrait;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Models\Region;
use App\Models\Experience;
use App\Modules\Event\Models\Serie;
use App\Modules\Event\Models\Sponsor;
use App\Models\Traits\BelongsToCity;
use App\Models\Traits\BelongsToVenue;
use App\Modules\Charity\Models\Charity;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Event\Models\EventPage;
use App\Modules\Charity\Models\Campaign;
use App\Modules\Event\Models\EventDetail;
use App\Modules\Event\Models\EventManager;
use App\Modules\Event\Models\ListingPage;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Charity\Models\ResalePlace;
use App\Modules\Charity\Models\CharityEvent;
use App\Modules\Event\Models\EventEventPage;
use App\Modules\Event\Models\EventThirdParty;
use App\Modules\Charity\Models\CampaignEvent;
use App\Modules\Event\Models\EventCustomField;
use App\Modules\Participant\Models\Participant;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Event\Models\EventEventManager;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Charity\Models\ResaleNotification;
use App\Modules\Event\Models\PromotionalFeaturedEvent;
use App\Modules\Charity\Models\CharityFundraisingEmail;
use App\Modules\Event\Models\EventCategoryPromotionalEvent;
use App\Modules\Charity\Models\CharityFundraisingEmailEvent;

trait EventRelations
{
    use SiteTrait, BelongsToCity, BelongsToVenue;

    /**
     * Get the region.
     *
     * @return BelongsTo
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the serie.
     * 
     * @return BelongsTo
     */
    public function serie(): BelongsTo
    {
        return $this->belongsTo(Serie::class);
    }

    /**
     * Get the sponsor
     *
     * @return BelongsTo
     */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * Get the event managers.
     *
     * @return BelongsToMany
     */
    public function eventManagers(): BelongsToMany
    {
        return $this->belongsToMany(EventManager::class, 'event_event_manager', 'event_id', 'event_manager_id')->using(EventEventManager::class)->withPivot('id', 'created_at', 'updated_at');
    }

    /**
     * Get the event categories that belong to the event.
     *
     * @return BelongsToMany
     */
    public function eventCategories(): BelongsToMany
    {
        return $this->belongsToMany(EventCategory::class, 'event_event_category', 'event_id', 'event_category_id')->using(EventEventCategory::class)->withPivot('id', 'ref', 'local_fee', 'international_fee', 'start_date', 'end_date', 'registration_deadline', 'withdrawal_deadline', 'total_places', 'classic_membership_places', 'premium_membership_places', 'two_year_membership_places')->withTimestamps();
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
     * Get all of the participants registered to event categories belonging to the event.
     */
    public function participants(): HasManyThrough
    {
        return $this->hasManyThrough(Participant::class, EventEventCategory::class, 'event_id', 'event_event_category_id');
    }

    /**
     * Get the event details for different sites.
     *
     * @return HasMany
     */
    public function eventDetails(): HasMany
    {
        return $this->hasMany(EventDetail::class);
    }

    /**
     * The campaigns that belong to the event.
     *
     * @return BelongsToMany
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)->using(CampaignEvent::class)->withPivot('id', 'created_at', 'updated_at');
    }

    /**
     * The charity fundraising emails that belong to the event.
     *
     * @return BelongsToMany
     */
    public function charityFundraisingEmail(): BelongsToMany
    {
        return $this->belongsToMany(CharityFundraisingEmail::class)->using(CharityFundraisingEmailEvent::class)->withPivot('id')->withTimestamps();
    }

    // /**
    //  * Get the event pages associated with the event.
    //  * @return HasMany
    //  */
    // public function eventPages(): HasMany
    // {
    //     return $this->hasMany(EventPage::class);
    // }

    /**
     * Get the event pages associated with the event.
     *
     * @return BelongsToMany
     */
    public function eventPages(): BelongsToMany
    {
        return $this->belongsToMany(EventPage::class, 'event_event_page', 'event_id', 'event_page_id')->using(EventEventPage::class)->withTimestamps();
    }

    // /**
    //  * Get the charities having an event page for the event.
    //  * Relationship between charities and events through eventpages table.
    //  *
    //  * @return BelongsToMany
    //  */
    // public function charities(): BelongsToMany
    // {
    //     return $this->belongsToMany(Charity::class, 'event_pages', 'event_id', 'charity_id');
    // }

    /**
     * Get the charities allowed and the ones not allowed to run the event.
     * Relationship between charities and events through charity_event table.
     *
     * @return BelongsToMany
     */
    public function includedExcludedCharities(): BelongsToMany
    {
        return $this->belongsToMany(Charity::class, 'charity_event', 'event_id', 'charity_id')->using(CharityEvent::class)->withPivot('id', 'type', 'created_at', 'updated_at');
    }

    /**
     * Get the charities allowed to run the event.
     * Some events require specific charities to run them (refer to the charities attribute on the Event model).
     * Relationship between charities and events through charity_event table.
     * @return BelongsToMany
     */
    public function includedCharities(): BelongsToMany
    {
        return $this->includedExcludedCharities()->wherePivot('type', 'included');
    }

    /**
     * Get the charities not allowed to run the event.
     * Some events require specific charities to run them (refer to the charities attribute on the Event model).
     * Relation between charities and events through charity_event table.
     * @return BelongsToMany
     */
    public function excludedCharities(): BelongsToMany
    {
        return $this->includedExcludedCharities()->wherePivot('type', 'excluded');
    }

    /**
     * Get the website enquiries associated with the event.
     * @return HasMany
     */
    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    /**
     * Get the number of places put on sale by the charity for the event.
     * @return HasMany
     */
    public function resalePlaces(): HasMany
    {
        return $this->hasMany(ResalePlace::class);
    }

    /**
     * Get the resale notifications associated with the event.
     * @return HasMany
     */
    public function resaleNotifications(): HasMany
    {
        return $this->hasMany(ResaleNotification::class);
    }

    /**
     * Get the listing pages associated with the event.
     * @return HasMany
     */
    public function listingPages(): HasMany
    {
        return $this->hasMany(ListingPage::class);
    }

    /**
     * Get the promotional featured events (linked or not linked to a county) associated with the event.
     * @return HasMany
     */
    public function promotionalFeaturedEvents(): HasMany
    {
        return $this->hasMany(PromotionalFeaturedEvent::class);
    }

    /**
     * Get the promotional featured events (linked to a county) associated with the event.
     * @return HasOne
     */
    public function promotionalFeaturedEventWithCounty(): HasOne
    {
        return $this->hasOne(PromotionalFeaturedEvent::class)->whereNotNull('county');
    }

    /**
     * Get the promotional featured events (not linked to a county) associated with the event.
     * @return HasOne
     */
    public function promotionalFeaturedEventWithoutCounty(): HasOne
    {
        return $this->hasOne(PromotionalFeaturedEvent::class)->whereNull('county');
    }

    /**
     * Get the promotional event category associated with the event.
     *
     * @return BelongsToMany
     */
    public function promotionalEventCategories(): BelongsToMany
    {
        return $this->belongsToMany(EventCategory::class, 'event_category_promotional_event', 'event_id', 'event_category_id')->using(EventCategoryPromotionalEvent::class)->withPivot('id', 'created_at', 'updated_at');
    }

    /**
     * Get the custom fields associated to the event.
     *
     * @return HasMany
     */
    public function eventCustomFields(): HasMany
    {
        return $this->hasMany(EventCustomField::class);
    }

    /**
     * Get the third parties integrated with the event.
     * NB: Use logic to ensure that the event third parties for a given event only gets associated with one partner (only one channel per partner) per site.
     *
     * @return HasMany
     */
    public function eventThirdParties(): HasMany
    {
        return $this->hasMany(EventThirdParty::class);
    }

    /**
     * Get the external enquiries associated with the event.
     *
     * @return HasMany
     */
    public function externalEnquiries(): HasMany
    {
        return $this->hasMany(ExternalEnquiry::class);
    }

    /*
     * Get the experiences associated with the event.
     *
     * @return BelongsToMany
     */
    public function experiences(): BelongsToMany
    {
        return $this->belongsToMany(Experience::class)->withTimestamps()->select(['name', 'icon', 'value', 'values', 'description']);
    }
}
