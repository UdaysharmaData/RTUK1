<?php

namespace App\Modules\Charity\Models;

use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Model;
use App\Enums\CharityMembershipTypeEnum;
use App\Modules\Event\Models\ListingPage;
use Illuminate\Database\Eloquent\Collection;
use App\Enums\CharityCharityListingTypeEnum;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
/**
 * Replaces PartnerListing model
 */
class CharityListing extends Model
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'charity_listings';

    protected $fillable = [
        'charity_id',
        'title',
        'slug',
        'description',
        'logo',
        'banner_image',
        'background_image',
        'url',
        'video',
        'show_2_year_members',
        'primary_color',
        'secondary_color',
        'charity_custom_title',
        'primary_partner_charities_custom_title',
        'secondary_partner_charities_custom_title'
    ];

    protected $casts = [
        'show_2_year_members' => 'boolean'
    ];

    /**
     * Get the charity that owns the listing.
     * The main charity on the listing.
     * @return BelongsTo
     */
    public function charity(): BelongsTo
    {
        return $this->belongsTo(Charity::class);
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
     * Get the partner charities associated to the listing.
     * @return Collection
     */
    public function partnerCharities(): Collection
    {
        return $this->charityCharityListings()->with(['charity' => function ($query) {
                $query->where('status', Charity::ACTIVE);
                if ($this->listingPage && $this->listingPage->event) { // get the event and the charity event page. For charity listings created through the listing pages with an event
                    $query->with('eventPages', function ($query){
                        $query->where('event_id', $this->listingPage->event_id);
                        $query->where('listing_page_id', $this->listingPage->id);
                    });
                }
            }])->where('type', 'primary_partner')->get();

        // $partnerCharities = $this->charityCharityListings();

        // if ($this->listingPage && $this->listingPage->event) { // get the event and the charity event page. For charity listings created through the listing pages with an event
        //     $partnerCharities = $partnerCharities->with(['charity.eventPages' => function ($query) {
        //         $query->where('event_id', $this->listingPage->event_id);
        //         // $query->where('listing_page_id', $this->listingPage->id);
        //     }]);
        // }
        // else { // for charity listings created through the charity listings
        //     $partnerCharities = $partnerCharities->with('charity');
        // }

        // $partnerCharities = $partnerCharities->where('type', 'primary_partner')->get();

        // return $partnerCharities;




        // return $this->charityCharityListings()->with(['charity', 'charityListing.listingPage.event'])->where('type', 'primary_partner');

        // return $this->charityCharityListings()->with(['charity.eventPages' => function ($query) {
        //     $query->where('event_id', $this->listingPage->event_id);
        // }, 'charityListing.listingPage.event'])->where('type', 'primary_partner');

        // return $this->charityCharityListings()->with(['charity', 'charityListing.listingPage.event.eventPages' => function($query) {
        //     $query->where('charity_id', );
        // }])->where('type', 'primary_partner');
    }

    /**
     * Get the secondary charities associated to the listing.
     * @return Collection
     */
    public function secondaryCharities(): Collection
    {
        return $this->charityCharityListings()->with(['charity' => function ($query) {
                $query->where('status', Charity::ACTIVE);
                if ($this->listingPage && $this->listingPage->event) { // get the event and the charity event page. For charity listings created through the listing pages with an event
                    $query->with('eventPages', function ($query){
                        $query->where('event_id', $this->listingPage->event_id);
                        $query->where('listing_page_id', $this->listingPage->id);
                    });
                }
            }])->where('type', 'secondary_partner')->get();
    }

    /**
     * Get the two_year charities associated to the listing.
     * @return Collection | null
     */
    public function twoYearCharities(): Collection | null
    {
        if (!$this->show_2_year_members) {
            return null;
        }

        $builder = Charity::with(['charityCharityListings' => function($query) {
            $query->where('charity_listing_id', $this->id)
                ->where('type', CharityCharityListingTypeEnum::TwoYear);
        }]);

        if ($this->listingPage && $this->listingPage->event) { // get the event and the charity event page. For charity listings created through the listing pages with an event
            $builder = $builder->with(['eventPages' => function ($query) {
                $query->where('event_id', $this->listingPage->event_id);
                $query->where('listing_page_id', $this->listingPage->id);
            }]);
        }

        $builder = $builder->whereHas('latestCharityMembership', function ($query) {
            $query->where('type', CharityMembershipTypeEnum::TwoYear)
                ->where('status', CharityMembership::ACTIVE);
        });
        
        $builder = $builder->where('status', Charity::ACTIVE);

        return $builder->get();

        // The code below can be used to get a charity listing with it's partnerCharities, SecondaryCharities and twoYearCharities.
        // $result = App\Modules\Charity\Models\CharityListing::with(['charityCharityListings', 'listingPage.event'])->oldest()->first();
        // $result->two_year_charities = $result->twoYearCharities();
        // $result->partner_charities = $result->partnerCharities();
        // $result->secondary_charities = $result->secondaryCharities();
    }

    /**
     * Get the ads to display under the charity listing
     * @return HasMany
     */
    public function charityListingAds(): HasMany
    {
        return $this->hasMany(CharityListingAd::class);
    }

    /**
     * Get the listing page associated with the charity listing.
     * @return HasOne
     */
    public function listingPage(): HasOne
    {
        return $this->hasOne(ListingPage::class);
    }
}
