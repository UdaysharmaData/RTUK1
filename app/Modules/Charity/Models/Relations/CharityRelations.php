<?php

namespace App\Modules\Charity\Models\Relations;

use App\Traits\SiteTrait;
use App\Enums\CharityUserTypeEnum;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventPage;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\User\Models\CharityUser;
use App\Modules\Charity\Models\Donation;
use App\Modules\Charity\Models\CallNote;
use App\Modules\Charity\Models\Campaign;
use App\Modules\Charity\Models\ResalePlace;
use App\Modules\Charity\Models\CharityEvent;
use App\Modules\Enquiry\Models\CharityEnquiry;
use App\Modules\Charity\Models\PartnerPackage;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Charity\Models\CharityListing;
use App\Modules\Charity\Models\CharityProfile;
use App\Modules\Charity\Models\CharityCategory;
use App\Modules\Participant\Models\Participant;
use App\Modules\Charity\Models\FundraisingEmail;
use App\Modules\Charity\Models\CharityMembership;
use App\Modules\Charity\Models\EventPlaceInvoice;
use App\Modules\Charity\Models\ResaleNotification;
use App\Modules\Charity\Models\CharityCharityListing;
use App\Modules\Charity\Models\CharityFundraisingEmail;

trait CharityRelations
{
    use SiteTrait;

    /**
     * Get the users having the owner, manager, user and participant type (relationship with the charity).
     * The relationship between the charity and its users is supposed to be a many-to-many relationship (as per the schema - for future use if requested) but since the application currently permits a charity to have just one owner, manager, user and many participants, we shall use this users relationship and make use of the relationships below too (for now) to get specific data.
     * users          - Get all the users having a type relationship with the charity.
     * charityOwner   - Get the user that owns the charity.
     * charityManager - Get the user (account_manager) that manages the charity.
     * charityUser    - Get the user (charity_user) that manages the charity.
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'charity_user', 'charity_id', 'user_id')->using(CharityUser::class)->withPivot('id', 'ref', 'type')->withTimeStamps();
    }

    /**
     * Get the charity owner - The user that owns the charity.
     * @return HasOne
     */
    public function charityOwner(): HasOne
    {
        // return $this->hasOne(CharityUser::class)->where('type', CharityUserTypeEnum::Owner)->latestOfMany(); // latestOfMany() Does not work with chained queries

        // return $this->hasOne(CharityUser::class)->ofMany(['id' => 'max'], function ($query) { // Correct & working
        //     $query->where('type', CharityUserTypeEnum::Owner);
        // });
        return $this->hasOne(CharityUser::class)->where('type', CharityUserTypeEnum::Owner)->latest('id');
    }

    /**
     * Get the charity manager - The user (account_manager) that manages the charity.
     * @return HasOne
     */
    public function charityManager(): HasOne
    {
        return $this->hasOne(CharityUser::class)->where('type', CharityUserTypeEnum::Manager)->latest('id');
    }

    /**
     * Get the charity users - The users (of role charity_user) that manages the charity.
     * NB: A charity has many charity_users (From the sport-for-api database records, multiple charity users have the same charity_id).
     * 
     * @return HasMany
     */
    public function charityUsers(): HasMany
    {
        return $this->hasMany(CharityUser::class)->where('type', CharityUserTypeEnum::User);
    }


    /**
     * Get the charity category.
     * @return BelongsTo
     */
    public function charityCategory(): BelongsTo
    {
        return $this->belongsTo(CharityCategory::class);
    }

    /**
     * Get the charity memberships.
     * @return HasMany
     */
    public function charityMemberships(): HasMany
    {
        return $this->hasMany(CharityMembership::class);
    }

    /**
     * Get the charity's most recent membership.
     * @return HasOne
     */
    public function latestCharityMembership(): HasOne
    {
        return $this->hasOne(CharityMembership::class)->latestOfMany();
    }

    /**
     * Get the charity's oldest membership.
     * @return HasOne
     */
    public function oldestCharityMembership(): HasOne
    {
        return $this->hasOne(CharityMembership::class)->oldestOfMany();
    }

    /**
     * Get the charity's previous membership (previous membership subscription).
     * @return CharityMembership | null
     */
    public function previousCharityMembership(): HasOne
    {
        return $this->hasOne(CharityMembership::class)->whereNotIn('id', $this->latestCharityMembership()->get()->pluck('id'))->orderByDesc('id')->latest();
    }

    /**
     * Get the fundraising emails that belong to the charity.
     * @return BelongsToMany
     */
    public function fundraisingEmails(): BelongsToMany
    {
        return $this->belongsToMany(FundraisingEmail::class)->using(CharityFundraisingEmail::class)->withPivot('id', 'status', 'content', 'from_name', 'from_email')->withTimestamps();
    }

    /**
     * Get the call notes that belong to the charity.
     * @return HasMany
     */
    public function callNotes(): HasMany
    {
        return $this->hasMany(CallNote::class);
    }

    /**
     * Get the campaigns that belong to the charity.
     * @return HasMany
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Get the charity listing that belong to the charity.
     * @return HasMany
     */
    public function charityListings(): HasMany
    {
        return $this->hasMany(CharityListing::class);
    }

    /**
     * Get the charities added as primary_partner, secondary_partner and 2_year_membership to the listing.
     * @return HasMany
     */
    public function charityCharityListings(): HasMany
    {
        return $this->hasMany(CharityCharityListing::class);
    }

    /**
     * Get the images that belong to the charity.
     * @return HasMany
     */
    public function charityImages(): HasMany
    {
        return $this->hasMany(CharityImage::class);
    }

    /**
     * Get the charity's profile for the site making the request.
     * @return HasOne
     */
    public function charityProfile(): HasOne
    {
        return $this->hasOne(CharityProfile::class)->where('site_id', static::getSite()?->id);
    }

    /**
     * Get the profiles that belong to the charity.
     * @return HasMany
     */
    public function charityProfiles(): HasMany
    {
        return $this->hasMany(CharityProfile::class);
    }

    /**
     * Get the donations that belong to the charity.
     * @return HasMany
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Get the charity enquiries that belong to the charity.
     * @return HasMany
     */
    public function charityEnquiries(): HasMany
    {
        return $this->hasMany(CharityEnquiry::class);
    }

    /**
     * Get the website enquiries associated with the charity.
     * @return HasMany
     */
    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    /**
     * Get the external enquiries associated with the charity.
     * @return HasMany
     */
    public function externalEnquiries(): HasMany
    {
        return $this->hasMany(ExternalEnquiry::class);
    }

    /**
     * Get the event pages of the charity.
     * @return HasMany
     */
    public function eventPages(): HasMany
    {
        return $this->hasMany(EventPage::class);
    }

    /**
     * Get the events for which the charity has an event page.
     * Relationship between charities and events through eventpages table.
     * @return BelongsToMany
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_pages', 'charity_id', 'event_id');
    }

    /**
     * Get some events the charity is allowed to run and the ones it is not allowed to run.
     * Relationship between charities and events through charity_event table.
     * @return BelongsToMany
     */
    public function eventsIncludedExcluded(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'charity_event', 'charity_id', 'event_id')->using(CharityEvent::class)->withPivot('id', 'type', 'created_at', 'updated_at');
    }

    /**
     * Get some events the charity is allowed to run.
     * Relationship between charities and events through charity_event table.
     * @return BelongsToMany
     */
    public function eventsIncluded(): BelongsToMany
    {
        return $this->eventsIncludedExcluded()->wherePivot('type', 'included');
    }

    /**
     * Get some events the charity is not allowed to run.
     * Relationship between charities and events through charity_event table.
     * @return BelongsToMany
     */
    public function eventsExcluded(): BelongsToMany
    {
        return $this->eventsIncludedExcluded()->wherePivot('type', 'excluded');
    }

    /**
     * Get the number of places a charity has for an event and want to sell them to charities that might be interested.
     * @return HasMany
     */
    public function resalePlaces(): HasMany
    {
        return $this->hasMany(ResalePlace::class);
    }

    /**
     * Get the resale notifications associated with the charity.
     * @return HasMany
     */
    public function resaleNotifications(): HasMany
    {
        return $this->hasMany(ResaleNotification::class);
    }

    /**
     * Get the charity's packages (assigned/paid).
     * @return BelongsToMany
     */
    public function partnerPackages(): BelongsToMany
    {
        return $this->belongsToMany(PartnerPackage::class)->using(CharityPartnerPackage::class)->withTimestamps();
    }

    /**
     * Get the event place invoices associated to the charity.
     * @return HasMany
     */
    public function eventPlaceInvoices(): HasMany
    {
        return $this->hasMany(EventPlaceInvoice::class);
    }

    /**
     * Get the participants associated to the charity.
     * These are users that participates to events under the charity.
     *
     * @return HasMany
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

}
