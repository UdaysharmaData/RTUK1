<?php

namespace App\Modules\Event\Models;

use App\Models\City;
use App\Models\Region;
use App\Models\Venue;
use DB;
use Str;
use Auth;
use Exception;
use Carbon\Carbon;
use Bkwld\Cloner\Cloneable;
use Laravel\Scout\Searchable;
use Illuminate\Http\Request;
use App\Contracts\Redirectable;
use App\Traits\RedirectableTrait;
use App\Http\Helpers\AccountType;
use App\Http\Helpers\FormatNumber;
use App\Contracts\CanHaveManyFaqs;
use App\Contracts\CanHaveManyViews;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Contracts\CanHaveManyInteractions;
use App\Jobs\AddEventToPromotionalPagesJob;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Contracts\CanHaveAnalyticsTotalCountData;
use App\Contracts\CanHaveManySearchHistories;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Services\Analytics\Traits\AnalyticsMixins;
use App\Contracts\Metables\CanHaveMetableResource;
use Illuminate\Database\Query\Builder as DBBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Events\PartnerCharityAttemptedRegistrationEvent;
use App\Contracts\Socialables\CanHaveManySocialableResource;
use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Contracts\Locationables\CanHaveManyLocationableResource;

use App\Modules\User\Models\User;
use App\Modules\Setting\Models\Setting;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\ResalePlace;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Event\Models\Relations\EventRelations;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\Event\Models\Traits\EventQueryScopeTrait;

use App\Traits\SiteTrait;
use App\Traits\SlugTrait;
use App\Traits\HasManyFaqs;
use App\Traits\HasManyViews;
use App\Traits\Drafts\DraftTrait;
use App\Traits\Metable\HasOneMeta;
use App\Traits\AddUuidRefAttribute;
use App\Traits\HasManyInteractions;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\HasManySearchHistories;
use App\Traits\Medalable\HasManyMedals;
use App\Traits\FilterableListQueryScope;
use App\Traits\Uploadable\HasManyUploads;
use App\Traits\Socialable\HasManySocials;
use App\Traits\HasAnalyticsTotalCountData;
use App\Traits\Locationable\HasManyLocations;
use App\Traits\UseDynamicallyAppendedAttributes;

use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\FeeTypeEnum;
use App\Enums\EventTypeEnum;
use App\Enums\EventStateEnum;
use App\Enums\EventArchivedEnum;
use App\Enums\EventReminderEnum;
use App\Enums\SocialPlatformEnum;
use App\Enums\EventCharitiesEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Http\Helpers\ExtraAttributes;
use App\Enums\EventCategoryVisibilityEnum;
use App\Modules\Event\Enums\EventRegistrationMethodEnum;
use App\Http\Helpers\EventHelper;
use App\Modules\Setting\Enums\OrganisationCodeEnum;
use App\Modules\Setting\Models\Site;

class Event extends Model implements
    CanHaveManyUploadableResource,
    CanHaveManySocialableResource,
    CanHaveMetableResource,
    CanUseCustomRouteKeyName,
    CanHaveManyFaqs,
    CanHaveManyLocationableResource,
    CanHaveManyViews,
    CanHaveManySearchHistories,
    CanHaveManyInteractions,
    Redirectable
{
    use Searchable {
        search as parentSearch;
    }

    use HasFactory,
      //  SlugTrait,
        DraftTrait,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SoftDeletes,
        SiteTrait,
        EventQueryScopeTrait,
        HasManyUploads,
        HasManySocials,
        HasOneMeta,
        EventRelations,
        Cloneable,
        HasManyFaqs,
        HasManyLocations,
        HasManyViews,
        HasManySearchHistories,
        HasManyInteractions,
        HasAnalyticsTotalCountData,
        HasManyMedals,
        UseDynamicallyAppendedAttributes,
        FilterableListQueryScope,
        RedirectableTrait;

    protected $table = 'events';

    protected $fillable = [
        'region_id',
        'city_id',
        'venue_id',
        'status',
        'name',
        'slug',
        'postcode',
        'country',
        'description',
        'video',
        'website',
        'review',
        'estimated',
        'reg_preferred_heat_time',
        'reg_raced_before',
        'reg_estimated_finish_time',
        'reg_tshirt_size',
        'reg_age_on_race_day',
        'reg_first_name',
        'reg_last_name',
        'reg_email',
        'reg_gender',
        'reg_dob',
        'reg_month_born_in',
        'reg_nationality',
        'reg_occupation',
        'reg_address',
        'reg_city',
        'reg_state',
        'reg_postcode',
        'reg_country',
        'reg_phone',
        'reg_emergency_contact_name',
        'reg_emergency_contact_phone',
        'reg_minimum_age',
        'reg_family_registrations',
        'reg_passport_number',
        'reg_ethnicity',
        'reg_weekly_physical_activity',
        'reg_speak_with_coach',
        'reg_hear_from_partner_charity',
        'reg_reason_for_participating',
        'born_before',
        'distance',
        'custom_preferred_heat_time_start',
        'custom_preferred_heat_time_end',
        'terms_and_conditions',
        'charity_checkout_event_page_id',
        'charity_checkout_event_page_url',
        'charity_checkout_raised',
        'charity_checkout_title',
        'charity_checkout_status',
        'charity_checkout_integration',
        'charity_checkout_created_at',
        'fundraising_emails',
        'resale_price',
        'reminder',
        'type',
        'partner_event',
        'registration_method',
        'charities',
        'exclude_charities',
        'exclude_website',
        'exclude_participants',
        'archived',
        'route_info_code',
        'route_info_description',
        'what_is_included_description',
        'how_to_get_there',
        'event_day_logistics',
        'spectator_info',
        'kit_list',
        'show_address'
    ];

    protected $casts = [
        'status' => 'boolean',
        'estimated' => 'boolean',
        'reg_preferred_heat_time' => 'boolean',
        'reg_raced_before' => 'boolean',
        'reg_first_name' => 'boolean',
        'reg_last_name' => 'boolean',
        'reg_email' => 'boolean',
        'reg_estimated_finish_time' => 'boolean',
        'reg_tshirt_size' => 'boolean',
        'reg_age_on_race_day' => 'boolean',
        'reg_gender' => 'boolean',
        'reg_dob' => 'boolean',
        'reg_month_born_in' => 'boolean',
        'reg_nationality' => 'boolean',
        'reg_occupation' => 'boolean',
        'reg_address' => 'boolean',
        'reg_city' => 'boolean',
        'reg_state' => 'boolean',
        'reg_postcode' => 'boolean',
        'reg_country' => 'boolean',
        'reg_phone' => 'boolean',
        'reg_emergency_contact_name' => 'boolean',
        'reg_emergency_contact_phone' => 'boolean',
        'reg_family_registrations' => 'boolean',
        'reg_passport_number' => 'boolean',
        'reg_ethnicity' => 'boolean',
        'reg_weekly_physical_activity' => 'boolean',
        'reg_speak_with_coach' => 'boolean',
        'reg_hear_from_partner_charity' => 'boolean',
        'reg_reason_for_participating' => 'boolean',
        'born_before' => 'date',
        'charity_checkout_integration' => 'boolean',
        'charity_checkout_created_at' => 'datetime',
        'fundraising_emails' => 'boolean',
        'reminder' => EventReminderEnum::class,
        'type' => EventTypeEnum::class,
        'partner_event' => 'boolean',
        'registration_method' => 'array',
        'charities' => EventCharitiesEnum::class,
        'exclude_charities' => 'boolean',
        'exclude_website' => 'boolean',
        'exclude_participants' => 'boolean',
        'archived' => 'boolean'
    ];

    protected $appends = [
        'state',
        'video_id',
        'withdrawable',
        'local_registration_fee_range',
        'international_registration_fee_range',
        'date_range',
        'registration_deadline_range',
        'withdrawal_deadline_range',
        'website_registration_method',
        'portal_registration_method',
    ];

    public static $actionMessages = [
        'force_delete' => 'Deleting the event(s) permanently will unlink it from enquiries, external enquiries, combinations and others. This action is irreversible.'
    ];

    const ACTIVE = 1; // Active event

    const INACTIVE = 0; // InActive event

    /**
     * Get the event's state
     *
     * @return Attribute
     */
    protected function state(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $state = EventStateEnum::Live;

                if (!$this->eventCategories->firstWhere('pivot.end_date', '>', Carbon::now())) {
                    $state = EventStateEnum::Expired;
                }

                // if (!$this->eventCategories->firstWhere('pivot.start_date', '>', Carbon::now())) {
                //     $state = EventStateEnum::Expired;
                // }

                if ($this->archived) {
                    $state = EventStateEnum::Archived;
                }

                return $state;
            },
        );
    }

    /**
     * Get the event's video_id
     *
     * @return Attribute
     */
    protected function videoId(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $videoId = null;

                if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $this->video, $match)) {
                    $videoId = $match[1];
                } else {
                    $videoId = substr($this->video, strrpos($this->video, "=") + 1);
                }

                return $videoId;
            },
        );
    }

    /**
     * Whether or not registrations to the event are withdrawable
     *
     * @return Attribute
     */
    protected function withdrawable(): Attribute
    {
        return Attribute::make(
            get: function ($value) {

                if (isset($this->eventCategories[0]) && $this->eventCategories[0]?->pivot->withdrawal_deadline) {
                    return true;
                }

                return false;
            },
        );
    }

    /**
     * Get the local registration fee range for the event categories.
     *
     * @return Attribute
     */
    protected function localRegistrationFeeRange(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->formatPriceRange(array_unique($this->eventCategories->sortBy('pivot.local_fee')->pluck('pivot.local_fee')->all()));
            },
        );
    }

    /**
     * Get the international registration fee range for the event categories.
     *
     * @return Attribute
     */
    protected function internationalRegistrationFeeRange(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->formatPriceRange(array_unique($this->eventCategories->sortBy('pivot.international_fee')->pluck('pivot.international_fee')->all()));
            },
        );
    }

    /**
     * Get the date range for the event categories.
     *
     * @return Attribute
     */
    protected function dateRange(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $startDate = $this->eventCategories->sortBy('pivot.start_date')->first()?->pivot->start_date;
                $endDate = $this->eventCategories->sortByDesc('pivot.end_date')->first()?->pivot->end_date;

                if ($startDate) {
                    $value[] = Carbon::parse($startDate);
                }

                if ($endDate) {
                    $value[] = Carbon::parse($endDate);

                    $value = array_unique($value);

                    $value = isset($value[1])
                        ? $value
                        : $value[0];
                }

                return $value;
            },
        );
    }

    /**
     * Get the registration deadline range for the event categories.
     *
     * @return Attribute
     */
    protected function registrationDeadlineRange(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->formatDateRange(array_unique($this->eventCategories->whereNotNull('pivot.registration_deadline')->sortBy('pivot.registration_deadline')->pluck('pivot.registration_deadline')->all()));
            },
        );
    }

    /**
     * Get the withdrawal deadline range for the event categories.
     *
     * @return Attribute
     */
    protected function withdrawalDeadlineRange(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->formatDateRange(array_unique($this->eventCategories->whereNotNull('pivot.withdrawal_deadline')->sortBy('pivot.withdrawal_deadline')->pluck('pivot.withdrawal_deadline')->all()));
            },
        );
    }

    /**
     * The url on the website.
     *
     * @return Attribute
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $value = "";

                if ($site = static::getSite()) {
                    switch ($site?->organisation?->code) {
                        case OrganisationCodeEnum::GWActive->value:
                            $value = $site?->url . "/event/$this->slug";
                            break;
                        default:
                            $value = $site?->url . "/events/$this->slug";
                            break;
                    }
                }

                return $value;
            },
        );
    }

    /**
     * The website registration method
     *
     * @return Attribute
     */
    protected function websiteRegistrationMethod(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->registration_method['website_registration_method'] ?? null;
            },
        );
    }

    /**
     * The portal registration method
     *
     * @return Attribute
     */
    protected function portalRegistrationMethod(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->registration_method['portal_registration_method'] ?? null;
            },
        );
    }

    /**
     * The formatted name.
     *
     * @return Attribute
     */
    protected function formattedName(): Attribute
    {
        return Attribute::make(
            get: function () {
                return ucwords(strtolower($this->name));
            },
        );
    }

    /**
     * Format price range
     *
     * @param  array    $fees
     * @return ?string
     */
    private function formatPriceRange(array $fees): ?string
    {
        if (count($fees) > 1) {
            $start = current($fees);
            $start = $start ?? (float) $start; // Ensure 0 gets displayed when value is null
            $end = end($fees);
            $end = $end ?? (float) $end;

            $value = FormatNumber::formatWithCurrency($start) . ' - ' . FormatNumber::formatWithCurrency($end);
        } else {
            $value = FormatNumber::formatWithCurrency($fees[0] ?? 0); // Ensure 0 gets displayed when value is null
        }

        return $value;
    }

    /**
     * Format date range
     *
     * @param  array    $dates
     * @return ?string
     */
    private function formatDateRange(array $dates): null|string|array
    {
        if (count($dates) > 1) {
            $start = current($dates);
            $end = end($dates);

            $value[] = Carbon::parse($start);
            $value[] = Carbon::parse($end);
        } else {
            $value = isset($dates[0]) ? Carbon::parse($dates[0]) : null;
        }

        return $value;
    }

    /**
     * Get the fee type the user is supposed to pay.
     *
     * @return FeeTypeEnum
     */
    public function feeType(): FeeTypeEnum
    {
        return EventHelper::feeType($this);
    }

    /**
     * Get total amount (total cost from participants registrations)
     *
     * @return array
     */
    public function amount(): array
    {
        $totalAmount = 0;

        foreach ($this->eventCategories as $_key => $eventCategory) {
            $amount = $eventCategory->pivot->amount();

            $this->eventCategories[$_key]->pivot->amount = $amount;
            $totalAmount += $amount['unformatted'];
        }

        $result['formatted'] = FormatNumber::formatWithCurrency($totalAmount = round($totalAmount, 2));
        $result['unformatted'] = $totalAmount;

        return $result;
    }

    /**
     * Get all active events accessible to only included charities.
     *
     * @return Collection
     */
    public static function onlyIncludedCharities(): Collection
    {
        return static::archived(static::INACTIVE)
            ->state(EventStateEnum::Live)
            ->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                });
            })
            ->where('charities', EventCharitiesEnum::Included)
            ->get();
    }

    // /**
    //  * TODO: Update this after creating the RaceFile model
    //  */
    // public function rankingsAverage($gender)
    // {
    //     $raceResults = $this->raceResults()
    //         ->whereHas('raceFile', function($query) {
    //             $query->where('state', 'published');
    //         })->whereHas('rawRaceResult', function($query) use ($gender) {
    //             $query->where('gender', $gender);
    //         })->orderBy('time', 'asc')->get();

    //     $fifteenPercent = (int) ((15/100) * $raceResults->count());

    //     $rankingsTime = 0;
    //     $rankingsCount = 0;

    //     for($i = $fifteenPercent; $i < ($raceResults->count() - $fifteenPercent); $i++) {
    //         $rankingsTime += Time::timeInSeconds($raceResults[$i]->time);
    //         $rankingsCount++;
    //     }

    //     return $rankingsCount > 0 ? Time::format((int) ($rankingsTime / $rankingsCount)) : null;
    // }

    protected $cloneable_relations = ['eventDetails', 'eventCategories', 'eventThirdParties', 'eventManagers', /* 'meta', 'eventPages', 'listingPages',*/ 'promotionalEventCategories'];

    // protected $clone_exempt_attributes = ['slug'];

    public function onCloning($src, $child = null)
    {
        $this->name = $this->name . ' - (Copy)';

        if (static::where('name', $this->name)->exists()) { // Append the copy number to the event's name (The number of times the event is being duplicated)
            $appearances = static::where('name', 'like', '%' . $this->name . '%')->count();

            $counter = 1;
            $originalName = $this->name . ' x';

            do {
                $this->name = $originalName . ($appearances + $counter);
                $counter++;
            } while (static::where('name', $this->name)->exists());
        }
    }

    /**
     * Get all the participants for a given event.
     *
     * @param  EventEventCategory   $eec
     * @param  ?Charity             $charity
     * @return Collection
     */
    public static function _participants(EventEventCategory $eec, ?Charity $charity = null): Collection
    {
        $participants = Participant::filterByAccess()
            ->where('event_event_category_id', $eec->id);

        if ($charity) {
            $participants = $participants->where('charity_id', $charity->id);
        }

        return $participants->get();
    }

    /**
     * Count all the participants for a given event.
     *
     * @param  EventEventCategory  $eec
     * @param  Charity             $charity
     * @return int
     */
    public static function participantsCount(EventEventCategory $eec, ?Charity $charity = null): int
    {
        $participants = Participant::filterByAccess()
            ->where('event_event_category_id', $eec->id);

        if ($charity) {
            $participants = $participants->where('charity_id', $charity->id);
        }

        return $participants->count();
    }

    /**
     * Get the number of participants the charity has paid for their places for a given event & event category.
     *
     * @param  EventEventCategory  $eec
     * @param  Charity             $charity
     * @return int
     */
    public static function charityPaidFor(EventEventCategory $eec, Charity $charity): int
    {
        return Participant::where('event_event_category_id', $eec->id)
            ->where('charity_id', $charity->id)
            ->where('waive', ParticipantWaiveEnum::Completely) // TODO: What about participants that have been partially exempted? Please look deeper into that
            ->has('invoiceItem')
            ->count();
    }

    /**
     * Get the number of participants the charity is still to pay for their places for a given event & event category
     *
     * @param  EventEventCategory  $eec
     * @param  Charity             $charity
     * @return int
     */
    public static function charityToPayFor(EventEventCategory $eec, Charity $charity): int
    {
        return Participant::where('event_event_category_id', $eec->id)
            ->where('charity_id', $charity->id)
            ->where('waive', ParticipantWaiveEnum::Completely) // TODO: What about participants that have been partially exempted? Please look deeper into that
            ->doesntHave('invoiceItem')
            ->count();
    }

    // /**
    //  *
    //  * TODO: Rename this method
    //  *
    //  * @param  EventEventCategory  $eec
    //  * @param  Charity $charity
    //  * @return int
    //  */
    // public static function CharityToPayForUser(EventEventCategory $eec, Charity $charity)
    // {
    //     return Participant::where('event_event_category_id', $eec->id)
    //          ->where('charity_id', $charity->id)
    //          ->where('exempt', 1)
    //          ->get();
    // }

    /**
     * Add the event pages associated with this event to the promotional pages.
     *
     * @return void
     */
    public function addToPromotionalPages(): void
    {
        try {
            DB::beginTransaction();

            PromotionalPage::with(['eventPageListing'])
                ->where('region_id', $this->region_id)
                ->chunk(20, function ($promotionalPages) {
                    foreach ($promotionalPages as $promotionalPage) {
                        if ($promotionalPage->eventPageListing->charity->status) { // Only add / (create and add) event pages to promotional pages of active charities

                            $doesntExist = $promotionalPage->eventPageListing->eventPages()->whereHas('events', function ($query) use ($promotionalPage) { // Check if the charity's event page exists and has been been added to the featured event pages of the event page listing of the promotional page
                                $query->where('charity_id', $promotionalPage->eventPageListing->charity_id)
                                    ->where('event_id', $this->id);
                            })->doesntExist();

                            $isAllowed = true;

                            if ($this->charities == EventCharitiesEnum::Included || $this->charities == EventCharitiesEnum::Excluded) { // Check if the charity is allowed to run the event.
                                $isAllowed = Charity::isAllowed($promotionalPage->eventPageListing->charity, $this);
                            }

                            if ($doesntExist && !$this->exclude_charities && $isAllowed) { // If the charity is allowed to run the event and the event page has not yet been attached as a featured event page, proceed

                                // Check if the event exists among the other events of the event page listing
                                $exists = $promotionalPage->eventPageListing->eventCategories()
                                    ->wherePivot('event_page_listing_id', $promotionalPage->event_page_listing_id)
                                    ->wherePivotIn('event_category_id', $this->eventCategories->pluck('pivot.event_category_id')->all())
                                    ->wherePivotIn('event_category_event_page_listing.id', function ($query) use ($promotionalPage) {
                                        $query->select('event_page_event_category_event_page_listing.event_category_event_page_listing_id')
                                            ->from('event_page_event_category_event_page_listing')
                                            ->whereIn('event_page_event_category_event_page_listing.event_page_id', function ($query) use ($promotionalPage) {
                                                $query->select('event_pages.id')
                                                    ->from('event_pages')
                                                    ->where('event_pages.charity_id', $promotionalPage->eventPageListing->charity_id)
                                                    ->whereIn('event_pages.id', function ($query) {
                                                        $query->select('event_event_page.event_page_id')
                                                            ->from('event_event_page')
                                                            ->where('event_id', $this->id);
                                                    });
                                            });
                                    })
                                    ->exists();

                                if (!$exists) { // If the event page has not yet been added to the other events of the event page listing, create (if it doesn't exists) and attach it
                                    $eventPage = EventPage::whereHas('events', function ($query) {
                                        $query->where('events.id', $this->id);
                                    })->where('charity_id', $promotionalPage->eventPageListing->charity_id)
                                        ->first();

                                    if (!$eventPage) { // Create the event page if it doesn't exists
                                        $eventPage = $promotionalPage->createEventPage($this);
                                    }

                                    foreach ($this->eventCategories as $category) { // Add the event page to the other events of the event page listing.
                                        $ecepl = EventCategoryEventPageListing::create(
                                            [
                                                'event_page_listing_id' => $promotionalPage->event_page_listing_id,
                                                'event_category_id' => $category->id
                                            ]
                                        );

                                        $ecepl->eventPages()->syncWithoutDetaching($eventPage->id);
                                    }
                                }
                            }
                        }
                    }
                });

            DB::commit();
        } catch (Exception $e) {
            Log::debug(json_encode($e));
            DB::rollback();
        }
    }

    /**
     * Archive an event
     *
     * @param  Event $event
     * @return object
     */
    public static function archive(Event $event): object
    {
        try {
            DB::beginTransaction();

            $clone = $event->duplicate();

            $clone->load(['eventCategories', 'eventThirdParties']);

            foreach ($clone->eventCategories as $category) {
                $category->pivot->start_date = Carbon::parse($category->pivot->start_date)->addYear()->toDateTimeString();
                $category->pivot->end_date = Carbon::parse($category->pivot->end_date)->addYear()->toDateTimeString();
                $category->pivot->registration_deadline = Carbon::parse($category->pivot->registration_deadline)?->addYear()->toDateTimeString();
                $category->pivot->withdrawal_deadline = $category->pivot->withdrawal_deadline
                    ? Carbon::parse($category->pivot->withdrawal_deadline)->addYear()->toDateTimeString()
                    : ($category->pivot->registration_deadline
                        ? Carbon::parse($category->pivot->registration_deadline)->subWeeks((int) config('app.event_withdrawal_weeks'))->toDateTimeString()
                        : null
                    );
                $category->pivot->save();
            }

            foreach ($clone->eventThirdParties as $thirdParty) {
                $_thirdParty = EventThirdParty::where('event_id', $event->id)
                    ->where('partner_channel_id', $thirdParty->partner_channel_id)
                    ->where('external_id', $thirdParty->external_id)
                    ->first();
                
                \Log::debug('Ran');

                foreach ($_thirdParty->eventCategories as $category) {
                    EventCategoryEventThirdParty::create([
                        'event_third_party_id' => $thirdParty->id,
                        'event_category_id' => $category->pivot->event_category_id,
                        'external_id' => $category->pivot->external_id
                    ]);

                    \Log::debug('Ran Inside');
                }
            }

            // Unlink the old event to it's event pages and link the new event to them
            EventEventPage::where('event_id', $event->id)->update(['event_id' => $clone->id]);

            // Update the name of the cloned event to that of the previous event and update that of the previous event (by appending the year of its start_date to it). This is to ensure that the cloned event (newly created) uses the name and slug of the previous event thereby keeping our SEO optimal in the sense that the url of the active event is always ranked (since it never changes). Everytime an event gets archived, a new url(slug) is created for the archived event.
            $clone->name = $event->name;
            $event->name = $event->name . ' ' . $event->eventCategories[0]->pivot->start_date->year;

            // Set the old event as archived
            $event->archived = EventArchivedEnum::Yes->value;
            $event->save();

            // Set the newly created event active
            $clone->status = Event::ACTIVE;
            $clone->save();

            $event->load(['address', 'city', 'eventCategories', 'eventManagers.user', 'eventThirdParties', 'excludedCharities', 'faqs.faqDetails.uploads', 'image', 'includedCharities', 'gallery', 'meta', 'region', 'routeInfoMedia', 'socials', 'whatIsIncludedMedia', 'venue']);
            $clone->load(['address', 'city', 'eventCategories', 'eventManagers.user', 'eventThirdParties', 'excludedCharities', 'faqs.faqDetails.uploads', 'image', 'includedCharities', 'gallery', 'meta', 'region', 'routeInfoMedia', 'socials', 'whatIsIncludedMedia', 'venue']);

            // Add the new event to the promotional pages.
            dispatch(new AddEventToPromotionalPagesJob($clone));

            DB::commit();
        } catch (QueryException $e) {
            DB::rollback();

            return (object) ['status' => false];
        }

        return (object) [
            'status' => true,
            'event'  => $event,
            'clone'  => $clone
        ];
    }

    // /**
    //  * List all active events
    //  *
    //  * @param  ?bool      $nonPartner
    //  * @return ?Collection
    //  */
    // public static function listAll($nonPartner = null): ?Collection
    // {
    //     $events = static::whereHas('eventCategories', function($query) {
    //             // $query->whereNotNull('local_fee')
    //             //     ->whereNotNull('international_fee')
    //             $query->whereHas('site', function ($query) {
    //                     $query->makingRequest();
    //                 });
    //         })->state(EventStateEnum::Live);

    //     if (! $nonPartner) {
    //         $events = $events->where('partner_event', static::ACTIVE);
    //     }

    //     if (AccountType::isCharityOwnerOrCharityUser()) {
    //         $events = $events->where(function($query) {
    //             $query->where('charities', EventCharitiesEnum::All)
    //                 ->orWhere(function ($query) {
    //                     $query->where('charities', EventCharitiesEnum::Included)
    //                         ->whereHas('includedCharities.users', function($query) {
    //                             $query->where('users.id', \Auth::user()->id);
    //                         });
    //                 })
    //                 ->orWhere(function ($query) {
    //                     $query->where('charities', EventCharitiesEnum::Excluded)
    //                         ->whereDoesntHave('excludedCharities.users', function($query) {
    //                             $query->where('users.id', \Auth::user()->id);
    //                         });
    //                 });
    //         });
    //     }

    //     if (AccountType::isEventManager()) {
    //         $events = $events->whereHas('eventManagers', function ($query) {
    //             $query->where('user_id', \Auth::user()->id);
    //         });
    //     }

    //     $events = $events->orderBy('name')->get();

    //     return $events;
    // }

    // /**
    //  * List all partner events
    //  *
    //  * @return static
    //  */
    // public static function partnerEvents(): static
    // {
    //     return static::with(['eventCategories', 'participants'])
    //         ->state(EventStateEnum::Live)
    //         ->partnerEvent(static::ACTIVE)
    //         ->whereHas('eventCategories', function($query) {
    //             $query->whereHas('site', function($query) {
    //                  $query->makingRequest();
    //             });

    //             $query->where('registration_deadline', '>=', Carbon::now());
    //         })
    //         ->get();
    // }

    // /**
    //  * List all the events managed by an event manager
    //  *
    //  * @param  User         $user
    //  * @return ?Collection
    //  */
    // public static function listAllByEventManager(User $user): ?Collection
    // {
    //     return static::whereHas('eventCategories', function($query) {
    //         $query->whereHas('site', function ($query) {
    //             $query->makingRequest();
    //         });
    //     })->whereHas('eventManagers', function($query) use ($user) {
    //         $query->where('user_id', $user->id);
    //     })->orderBy('name')
    //     ->get();
    // }

    /**
     * Get events (for dropdown fields).
     *
     * @param  Request    $request
     * @return DBBuilder
     */
    public static function queryAll(Request $request): DBBuilder
    {
        if (($request->filled('state') && (EventStateEnum::from($request->state) == EventStateEnum::Live)) || AccountType::isParticipant()) { // Live events are always active
            $request['active'] = true;
        }

        $events = DB::table('events')
            ->select('events.id', 'events.ref', 'events.name', 'events.slug', ...ExtraAttributes::get())
            ->whereNull('deleted_at');

        if ($request->filled('term')) {
            $events = $events->where('name', 'like', "%{$request->term}%");
        }

        if ($request->filled('active')) { // Return active or non-active events
            if ($request->active) {
                $events = $events->where('estimated', Event::INACTIVE)
                    ->where('status', Event::ACTIVE)
                    ->where('archived', Event::INACTIVE);
            } else {
                $events = $events->where(function ($query) {
                    $query->where('estimated', Event::ACTIVE)
                        ->orWhere('status', Event::INACTIVE)
                        ->orWhere('archived', Event::ACTIVE);
                });
            }
        }

        if ($request->filled('state') && (EventStateEnum::from($request->state) == EventStateEnum::Archived)) { // Return archived events
            $events = $events->where('archived', EventArchivedEnum::Yes->value);
        }

        $events = $events->whereIn('events.id', function ($query) use ($request) {
            $query->select('event_event_category.event_id')
                ->from('event_event_category');
            // ->whereNotNull('event_event_category.local_fee')
            // ->whereNotNull('event_event_category.international_fee')

            if ($request->filled('state')) {
                if (EventStateEnum::from($request->state) == EventStateEnum::Live) { // Return live events
                    $query->where('event_event_category.end_date', '>', Carbon::now());
                } else if (EventStateEnum::from($request->state) == EventStateEnum::Expired) { // Return expired events
                    $query->where('event_event_category.end_date', '<', Carbon::now());
                }
            }

            if (AccountType::isParticipant()) { // Always return live events for this user
                $query->where('event_event_category.end_date', '>', Carbon::now());
            }

            $query->whereIn('event_event_category.event_category_id', function ($query) {
                $query->select('event_categories.id')
                    ->from('event_categories')
                    ->whereIn('event_categories.site_id', function ($query) {
                        $query->select('sites.id')
                            ->from('sites');

                        if (!AccountType::isGeneralAdmin()) { // Only the general admin has access to all platforms
                            $query->where('sites.id', static::getSite()?->id);
                        }
                    });
            });
        });

        if (AccountType::isCharityOwnerOrCharityUser()) {
            $events = $events->where(function ($query) {
                $query->where('charities', EventCharitiesEnum::All)
                    ->orWhere(function ($query) { // Include events for which the charity has been included
                        $query->where('charities', EventCharitiesEnum::Included);

                        $query->whereIn('events.id', function ($query) {
                            $query->select('charity_event.event_id')
                                ->from('charity_event')
                                ->where('charity_event.type', CharityEventTypeEnum::Included)
                                ->whereIn('charity_event.charity_id', function ($query) {
                                    $query->select('charity_user.charity_id')
                                        ->from('charity_user')
                                        ->where('charity_user.user_id', Auth::user()->id);
                                });
                        });
                    })
                    ->orWhere(function ($query) { // Don't include the events for which the charity has been excluded
                        $query->where('charities', EventCharitiesEnum::Excluded);

                        $query->whereNotIn('events.id', function ($query) {
                            $query->select('charity_event.event_id')
                                ->from('charity_event')
                                ->where('charity_event.type', CharityEventTypeEnum::Excluded)
                                ->whereIn('charity_event.charity_id', function ($query) {
                                    $query->select('charity_user.charity_id')
                                        ->from('charity_user')
                                        ->where('charity_user.user_id', Auth::user()->id);
                                });
                        });
                    });
            });
        }

        if (AccountType::isEventManager()) {
            $events = $events->whereIn('events.id', function ($query) {
                $query->select('event_event_manager.event_id')
                    ->from('event_event_manager')
                    ->whereIn('event_event_manager.event_manager_id', function ($query) {
                        $query->select('event_managers.id')
                            ->from('event_managers')
                            ->where('event_managers.user_id', Auth::user()->id);
                    });
            });
        }

        return $events;
    }

    /**
     * Get events with their event categories (for dropdown fields).
     *
     * @param  Request       $request
     * @return Builder
     */
    public static function queryAllWithCategories(Request $request): Builder
    {
        $events = static::select('events.id', 'events.ref', 'events.name', 'events.slug', ...ExtraAttributes::get())
            ->with(['eventCategories:id,ref,name,slug', 'eventCategories' => function ($query) use ($request) {
                if ($request->filled('with.visibility')) {
                    $query->visibility(EventCategoryVisibilityEnum::from($request->with['visibility']));
                }

                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                })->withoutAppends();
            }])->filterByAccess();

        if ($request->filled('term')) {
            $events = $events->where('name', 'like', "%{$request->term}%");
        }

        if ($request->filled('active')) { // return active events
            $events = $events->active($request->active);
        }

        if ($request->filled('state')) { // return events based on the state
            $events = $events->state(EventStateEnum::from($request->state));
        }

        $events = $events->whereHas('eventCategories', function ($query) use ($request) {
            $query->whereHas('site', function ($query) {
                $query->makingRequest();
            });
        });

        return $events;
    }

    /**
     * Get events with their third parties (for dropdown fields)
     *
     * @param  Request  $request
     * @return Builder
     */
    public static function queryAllWithThirdParties(Request $request): Builder
    {
        $events = static::select('events.id', 'events.ref', 'events.name', 'events.slug', ...ExtraAttributes::get())
            ->withoutAppends()
            ->with(['eventCategories:id,ref,name,slug', 'eventCategories' => fn ($query) => $query->withoutAppends(), 'eventThirdParties.partnerChannel.partner:id,ref,name,slug,code', 'eventThirdParties.partnerChannel.partner' => function ($query) use ($request) {
                if ($request->filled('with.visibility')) {
                    $query->visibility(EventCategoryVisibilityEnum::from($request->with['visibility']));
                }

                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                });
            }, 'eventThirdParties.eventCategories' => function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                });
            }, 'eventThirdParties.eventCategories:id,ref,name,slug'])->filterByAccess();

        if ($request->filled('term')) {
            $events = $events->where('name', 'like', "%{$request->term}%");
        }

        if ($request->filled('active')) { // return active events
            $events = $events->active($request->active);
        }

        if ($request->filled('state')) { // return events based on the state
            $events = $events->state(EventStateEnum::from($request->state));
        }

        $events = $events->whereHas('eventCategories', function ($query) use ($request) {
            $query->whereHas('site', function ($query) {
                $query->makingRequest();
            });
        })->has('eventThirdParties');

        return $events;
    }

    /**
     * Get the latest tweet for a given event.
     *
     * @param  Event $event
     * @return ?object
     */
    public static function latestTweet(Event $event): ?object
    {
        $result = null;

        $headers = [
            'Content-Type' => 'application/json'
        ];

        $twitterHandle = $event->socials()->where('platform', SocialPlatformEnum::Twitter)->first()?->handle;

        if ($twitterHandle) { // Get the twitter user (for the event twitter handle) by username
            $url = "https://api.twitter.com/2/users/by/username/{$twitterHandle}";

            $response = Http::withHeaders($headers)
                ->withToken(config('apiclient.twitter_bearer_token'))
                ->accept('application/json')
                ->get($url)
                ->json();

            if (isset($response['data']) && isset($response['data']['id'])) { // Get the latest tweet made from the event twitter handle
                $userId = $response['data']['id'];

                $queryParams = [
                    'max_results' => 5,
                    'tweet.fields' => 'created_at,text,attachments',
                    'expansions' => 'attachments.media_keys,author_id',
                    'user.fields' => 'created_at,name,username,profile_image_url',
                    'media.fields' => 'duration_ms,height,media_key,preview_image_url,type,url,width,public_metrics,alt_text,variants'
                ];

                $url = "https://api.twitter.com/2/users/{$userId}/tweets?" . http_build_query($queryParams);

                $response = Http::withHeaders($headers)
                    ->withToken(config('apiclient.twitter_bearer_token'))
                    ->accept('application/json')
                    ->get($url)
                    ->json();

                if (isset($response['data']) && (count($response['data']) > 0)) {
                    $result = [];
                    $result = $response['data'][0]; // Get the latest tweet

                    if (isset($response['includes']['users']) && isset($response['includes']['users'][0])) { // Get the author of the tweet
                        $result['user'] = $response['includes']['users'][0];
                    }

                    if (isset($result['attachments']['media_keys']) && isset($result['attachments']['media_keys'][0])) { // Check if the tweet has a media
                        if (isset($response['includes']['media']) && isset($response['includes']['media'][0])) {
                            $result['media'] = [];

                            $result['media'] = collect($response['includes']['media'])->filter(function ($value, $key) use ($result) { // Get the media belonging to the tweeet
                                return in_array($value['media_key'], $result['attachments']['media_keys']);
                            });
                        }
                    }
                } else if (isset($response['errors'])) {
                    // TODO: Log this to the developers slack channel to report the error.
                }
            }
        }

        return $result
            ? (object) $result
            : null;
    }

    /**
     * Get the registrations summary for an event.
     * TODO: @tsaffi - Revise this logic based on the similar revision made on SFC
     *
     * @param  Event  $event
     * @return object
     */
    public static function registrationsSummary(Event $event): object
    {
        $results = (object) [
            'all' => (object) [
                'registrations' => 0,
                'complete_registrations' => 0,
                'complete_registrations_percentage' => 0,
                'total_places' => 0,
                'remaining_places' => 0
            ],
            'categories' => []
        ];

        foreach ($event->eventCategories as $category) {
            $results->categories = [
                ...$results->categories,
                [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'registrations' => $registrations = $category->pivot->participants()->filterByAccess()->count(),
                    'complete_registrations' => $completeRegistrations = $category->pivot->participants()->filterByAccess()->completedRegistration()->count(),
                    'total_places' => $totalPlaces = (AccountType::isCharityOwnerOrCharityUser() ? $category->pivot->placesAllocatedToCharityMembership(Auth::user()->charityUser?->charity) : $category->pivot->total_places),
                    'remaining_places' => AccountType::isCharityOwnerOrCharityUser()
                        ? $category->pivot->charityHasAvailablePlaces(Auth::user()->charityUser?->charity)?->places
                        : (isset($category->pivot->total_places)
                            ? $totalPlaces - $registrations
                            : null),
                    'complete_registrations_percentage' => round(($registrations ? (($completeRegistrations / $registrations) * 100) : 0), 2)
                ]
            ];

            $results->all->total_places += (int) $totalPlaces;
        }

        $results->all->registrations = $event->registrations;

        $results->all->complete_registrations = $event->complete_registrations;

        $results->all->complete_registrations_percentage = round(($results->all->registrations ? (($results->all->complete_registrations / $results->all->registrations) * 100) : 0), 2);

        return $results;
    }

    /**
     * Checkout on Lets Do This platform (participant registration)
     *
     * @param  string  $races    // The race ids (event categories) on the LDT platform. NB: Races (event categories) are not shared among events on LDT. That is, races (event categories) get created for each event on LDT.
     * @param  integer $eventId  // The external_id (event_id equivalent id on LDT) of the first event
     * @return ?string
     */
    public static function checkoutOnLDT(string $races, $eventId,$site_id): ?string
    {
        // Query params
        $eventId = 189561; // NB: This value must be this constant.
        $baseUrl = config('apiclient.ldt_checkout_url');
        $origin = "runthrough";
        $utmOrganiserId = 69173; // RunThrough id (identifier) on the LDT platform
        $site = Site::find($site_id);

        $urlAddon = $eventId . '/race-selection?preferred=true&utm_source='.$site->code.'&utm_medium=organiser_referral&utm_campaign=preferred&utm_organiser_id=' . $utmOrganiserId . '&event_id=' . $eventId . '&origin=' . $origin . '&lraces=' . $races;
        $encodedPath = base64_encode('/gb/e/' . $urlAddon);
        $checkoutUrl = $baseUrl . $encodedPath;

        return $checkoutUrl;
    }

    /**
     * Get the indexable data array for the model.
     * This is used by Laravel Scout to build the search index.
     *
     * @return array
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => '',
            // 'event_categories.name' => ''
        ];
    }

    /**
     * Override the default laravel scout search method.
     *
     * @param  string $query
     * @param  \Closure  $callback
     * @return \Laravel\Scout\Builder
     */
    public static function search($query = '', $callback = null): \Laravel\Scout\Builder
    {
        return static::parentSearch($query, $callback)->query(
            function ($builder) {
                $builder->withOnly(['eventCategories','image'])
                    ->appendsOnly([
                        'local_registration_fee_range',
                        'international_registration_fee_range',
                        'date_range',
                        'registration_deadline_range',
                    ])
                    ->join('event_event_category', 'events.id', '=', 'event_event_category.event_id')
                    ->join('event_categories', 'event_event_category.event_category_id', '=', 'event_categories.id')
                    ->where('event_categories.site_id', static::getSite()->id)
                   // ->where('event_categories.visibility', EventCategoryVisibilityEnum::Public->value)
                    ->state(EventStateEnum::Live)
                    ->distinct('events.id')
                    ->orderBy('event_event_category.start_date')
                    ->orderBy('events.name')
                    ->select('events.id', 'events.ref', 'events.name', 'events.slug', 'event_event_category.start_date');
            }
        );
    }

    /**
     * Get the fee type options
     * 
     * @param  Event               $event
     * @param  EventEventCategory  $eec
     * @return \Illuminate\Support\Collection
     */
    public static function feeTypeOptions(?Event $event = null, ?EventEventCategory $eec = null): \Illuminate\Support\Collection
    {
        $options = [];

        if ($eec) {
            if ($eec->local_fee) {
                $options[] = FeeTypeEnum::Local->value;
            }

            if ($eec->international_fee) {
                $options[] = FeeTypeEnum::International->value;
            }
        } else if ($event) {
            foreach ($event->eventCategories as $category) {
                if ($category->pivot->local_fee) {
                    $options[] = FeeTypeEnum::Local->value;
                }

                if ($category->pivot->international_fee) {
                    $options[] = FeeTypeEnum::International->value;
                }
            }
        }

        $options = array_unique($options);

        $options = array_diff(array_column(FeeTypeEnum::cases(), 'value'), $options); // Get the exceptions - Feetypes not found in the event or eec

        $_options = [];

        foreach ($options as $option) {
            $_options[] = FeeTypeEnum::from($option);
        }

        return FeeTypeEnum::_options($_options);
    }

    public function regions_relationship()
    {
        return $this->belongsToMany(Region::class, 'event_region_linking', 'event_id', 'region_id');
    }
    public function cities_relationship()
    {
        return $this->belongsToMany(City::class, 'event_city_linking', 'event_id', 'city_id');
    }
    public function venues_relationship()
    {
        return $this->belongsToMany(Venue::class, 'event_venues_linking', 'event_id', 'venue_id');
    }
}
