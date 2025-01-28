<?php

namespace App\Modules\Event\Models;

use DB;
use Auth;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Contracts\InvoiceItemables\CanHaveManyInvoiceItemableResource;

use App\Http\Helpers\EventHelper;
use App\Http\Helpers\AccountType;
use App\Http\Helpers\FormatNumber;

use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\FeeTypeEnum;
use App\Modules\Setting\Enums\OrganisationEnum;
use App\Enums\EnquiryActionEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\CharityMembershipTypeEnum;
use App\Enums\EventTypeEnum;
use App\Enums\SettingCustomFieldTypeEnum;

use App\Traits\SiteTrait;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;

use \App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\Charity;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Setting\Models\Setting;
use App\Modules\Charity\Models\ResalePlace;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\Relations\EventEventCategoryRelations;
use App\Modules\User\Models\User;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EnquiryDataService;
use App\Services\DataServices\EventClientDataService;

use App\Traits\InvoiceItemable\HasManyInvoiceItems;

class EventEventCategory extends Pivot implements CanHaveManyInvoiceItemableResource
{
    use HasFactory,
        SiteTrait,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        EventEventCategoryRelations,
        HasManyInvoiceItems;

    /**
     * @var bool
     */
    private Site|null $site;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->site = static::getSite();
    }

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $table = 'event_event_category';

    protected $fillable = [
        'event_id',
        'event_category_id',
        'local_fee',
        'international_fee',
        'start_date',
        'end_date',
        'registration_deadline',
        'withdrawal_deadline',
        'total_places',
        'classic_membership_places',
        'premium_membership_places',
        'two_year_membership_places'
    ];

    protected $casts = [
        'local_fee' => 'double',
        'international_fee' => 'double',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'withdrawal_deadline' => 'datetime',
        'total_places' => 'integer',
        'classic_membership_places' => 'integer',
        'premium_membership_places' => 'integer',
        'two_year_membership_places' => 'integer'
    ];

    protected $appends = [
        'registration_fee',
        'local_registration_fee',
        'international_registration_fee',
        'formatted_local_registration_fee',
        'formatted_international_registration_fee',
        'formatted_registration_fee',
        'reg_status'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'registration_deadline',
        'withdrawal_deadline',
        'created_at',
        'updated_at'
    ];

    /**
     * Get the registration fee (amount to be paid by the user)
     *
     * @return Attribute
     */
    protected function registrationFee(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $event = Event::withTrashed()
                    ->withDrafted()
                    ->find($this->event_id);

                if ($event->feeType() == FeeTypeEnum::International) {
                    return $this->international_registration_fee;
                }

                return $this->local_registration_fee;
            }
        );
    }

    /**
     * Get the local registration fee of the category of the event.
     *
     * @return Attribute
     */
    protected function localRegistrationFee(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $fee = $this->local_fee ?? 0.00;
                $rate = (float) env('PARTICIPANT_REGISTRATION_CHARGE_RATE') / 100;
                $charge = $rate * $fee;
                $fee += $charge;
                $fee = round($fee, 2);

                return $fee;
            },
        );
    }

    /**
     * Get the international registration fee of the category of the event.
     *
     * @return Attribute
     */
    protected function internationalRegistrationFee(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $fee = $this->international_fee ?? 0.00;
                $rate = (float) env('PARTICIPANT_REGISTRATION_CHARGE_RATE') / 100;
                $charge = $rate * $fee;
                $fee += $charge;
                $fee = round($fee, 2);
        
                return $fee;
            },
        );
    }

    /**
     * Get the formatted local registration fee of the category of the event.
     *
     * @return Attribute
     */
    protected function formattedLocalRegistrationFee(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return FormatNumber::formatWithCurrency($this->local_registration_fee);
            },
        );
    }

    /**
     * Get the formatted international registration fee of the category of the event.
     *
     * @return Attribute
     */
    protected function formattedInternationalRegistrationFee(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return FormatNumber::formatWithCurrency($this->international_registration_fee);
            },
        );
    }

    /**
     * Get the formatted registration fee of the category of the event
     *
     * @return Attribute
     */
    protected function formattedRegistrationFee(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return FormatNumber::formatWithCurrency($this->registration_fee);
            },
        );
    }

    /**
     * Get the user registration status.
     *
     * @return Attribute
     */
    protected function regStatus(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($user = Auth::user()) {
                    return Participant::isRegisteredToEEC($user, $this)->status;
                }

                return false;
            },
        );
    }

    /**
     * Get the registration fee for a given user
     *
     * @param  User    $user
     * @return float
     */
    public function userRegistrationFee(User $user): float
    {
        $event = Event::withTrashed()
            ->withDrafted()
            ->find($this->event_id);

        if (EventHelper::feeType($event, $user) == FeeTypeEnum::International) {
            return $this->international_registration_fee;
        }

        return $this->local_registration_fee;
    }

    /**
     * Get the formatted registration fee for a given user
     *
     * @param  User    $user
     * @return float
     */
    public function formattedUserRegistrationFee(User $user): float
    {
        return FormatNumber::formatWithCurrency($this->userRegistrationFee($user));
    }

    /**
     * Get the amount.
     * 
     * TODO: Revise this logic when working on RunForCharity
     *
     * @return array
     */
    public function amount(): array
    {
        $ldtAmount = $this->local_registration_fee * $this->participants()
            ->has('externalEnquiry')
            ->doesntHave('invoiceItem')
            ->where('waive', ParticipantWaiveEnum::Completely)
            ->where('waiver', ParticipantWaiverEnum::Partner)
            ->count(); // LDT participants. They are normally not supposed to have an invoiceItem (Unless they click on the generate invoice button. TODO: Check and revise this logic)

        $localAmount = $this->local_registration_fee * $this->participants()
            ->doesntHave('externalEnquiry')
            ->whereHas('invoiceItem', fn ($query) => $query->where('price', $this->local_registration_fee))
            ->whereNull('waive')
            ->whereNull('waiver')
            ->count();

        $internationalAmount = $this->international_registration_fee * $this->participants()
            ->doesntHave('externalEnquiry')
            ->whereHas('invoiceItem', fn ($query) => $query->where('price', $this->international_registration_fee))
            ->whereNull('waive')
            ->whereNull('waiver')
            ->count();

        $waiveAmount = $this->local_fee * $this->participants()
            ->doesntHave('externalEnquiry')
            ->where('waive', ParticipantWaiveEnum::Completely)
            ->where('waiver', ParticipantWaiverEnum::Partner)
            ->count();

        $amount = (! $this->local_fee && ! $this->international_registration_fee) ? 'N/A' : round(($ldtAmount + $localAmount + $internationalAmount + $waiveAmount), 2); // For cases where the event is free (fees are nullable)

        $result = [];
        $result['formatted'] = is_string($amount) ? $amount : FormatNumber::formatWithCurrency($amount);
        $result['unformatted'] = is_string($amount) ? 0 : $amount;

        return $result;
    }
    
    /**
     * Check if the event has expired
     * @return bool
     */
    public function hasExpired(): bool
    {
        if ($this->event->type == EventTypeEnum::Rolling) {
            return false;
        }

        return $this->end_date->isPast();
    }
    
    /**
     * Check if the event is withdrawable
     *
     * @return bool
     */
    public function isWithdrawable(): bool
    {
        if ($this->withdrawal_deadline) {
            $deadline = $this->withdrawal_deadline ;
        } else if($this->registration_deadline) {
            $deadline = $this->registration_deadline->subWeeks((int)config('app.event_withdrawal_weeks'));
        } else {
            return false;
        }
        
        return $deadline->isFuture();
    }

    /**
     * Check if registrations are still active for the given event category of the event
     *
     * @param  Request  $request
     * @return object
     */
    public function registrationActive(?Request $request = null): object
    {
        $result = $this->isActive($request); // Check if the event is active

        if (! $result->status) {
            return $result;
        }

        if (! app()->runningInConsole()) { // Don't run this block when running in console. TODO: Improve on this check by referencing the 'external-enquiry:fetch-from-ldt' command here.
            if ($this->registration_deadline) {
                $now = Carbon::now();
                $regDeadline = Carbon::parse($this->registration_deadline);

                if ($now->greaterThanOrEqualTo($regDeadline)) {
                    return (object) [
                        'status' => false,
                        'message' => 'The event registrations are no longer active.'
                    ];
                }
            }
        }

        return (object) [
            'status' => true,
            'message' => 'The event registrations are still active.'
        ];
    }

    public function registrationSingleActive(?Request $request = null): object
    {
        $result = $this->isSingleActive($request); // Check if the event is active

        if (! $result->status) {
            return $result;
        }

        if (! app()->runningInConsole()) { // Don't run this block when running in console. TODO: Improve on this check by referencing the 'external-enquiry:fetch-from-ldt' command here.
            if ($this->registration_deadline) {
                $now = Carbon::now();
                $regDeadline = Carbon::parse($this->registration_deadline);

                if ($now->greaterThanOrEqualTo($regDeadline)) {
                    return (object) [
                        'status' => false,
                        'message' => 'The event registrations are no longer active.'
                    ];
                }
            }
        }

        return (object) [
            'status' => true,
            'message' => 'The event registrations are still active.'
        ];
    }

    /**
     * Check if an event is active
     *
     * @param  Request  $request
     * @return object
     */
    public function isActive(?Request $request = null): object
    {
        $event = $this->event;

        if ($event && !$event->deleted_at && !$event->drafted_at) { // Ensure the event is not soft deleted nor drafted
            $event = $event->where('status', Event::ACTIVE)
                ->where('archived', Event::INACTIVE)
                ->where('ref', $event->ref)
                ->whereHas('eventCategories', function ($query) use ($request) {
                    $query->whereHas('site', function ($query) use ($request) {
                        $query->when(app()->runningInConsole() && $request->filled('site_id'),function ($query) use ($request) {
                                $query->where('id',$request['site_id']);
                            }, function ($query){
                                $query->makingRequest();
                            });
                    });
                })->first();

            if ($event) { // Check if the event is active
                if ($event->estimated) { // Check if it is an estimated event
                    if ($request && (! Auth::check() || AccountType::isParticipant()) && ! app()->runningInConsole()) { // The request should not be authenticated or the authenticated user's active role should be participant
                        if ($request->email) {
                            Enquiry::updateOrCreate([
                                'site_id' => $request['site_id'],
                                'event_id' => $this->event_id,
                                'event_category_id' => $this->event_category_id,
                                'email' => $request->email,
                                'charity_id' => Charity::where('ref', $request->charity)->value('id'),
                            ], [
                                'first_name' => $request->first_name ?? null,
                                'last_name' => $request->last_name ?? null,
                                'action' => EnquiryActionEnum::RegistrationFailed_EstimatedEvent
                            ]);
                        }
                    }

                    return (object) [
                        'status' => false,
                        'message' => 'You can\'t register for an estimated event.'
                    ];
                }
            } else {
                return (object) [
                    'status' => false,
                    'message' => 'The event is not active.'
                ];
            }
        } else {
            // TODO: Notify the admin and developers about this
            Log::channel(static::getSite()?->code . 'adminanddeveloper')->debug('Registration attempt to deleted or drafted event: ' . json_encode($this->load('event')));

            return (object) [
                'status' => false,
                'message' => 'The event is not active.'
            ];
        }

        return (object) [
            'status' => true,
            'message' => 'The event registrations are still active.'
        ];
    }

    public function isSingleActive(?Request $request = null): object
    {
        $event = $this->event;
        if ($event && !$event->deleted_at && !$event->drafted_at) { // Ensure the event is not soft deleted nor drafted
            $event = $event
                //->where('status', Event::ACTIVE)
                ->where('archived', Event::INACTIVE)
                ->where('ref', $event->ref)
                ->whereHas('eventCategories', function ($query)use($request) {
                    $query->whereHas('site', function ($query)use($request) {
                        $query->where('id',$request['site_id']);
                    });
                })->first();

            if ($event) { // Check if the event is active
                if ($event->estimated) { // Check if it is an estimated event
                    if ($request && (! Auth::check() || AccountType::isParticipant()) && ! app()->runningInConsole()) { // The request should not be authenticated or the authenticated user's active role should be participant
                        if ($request->email) {
                            Enquiry::updateOrCreate([
                                'site_id' => $request['site_id'],
                                'event_id' => $this->event_id,
                                'event_category_id' => $this->event_category_id,
                                'email' => $request->email,
                                'charity_id' => Charity::where('ref', $request->charity)->value('id'),
                            ], [
                                'first_name' => $request->first_name ?? null,
                                'last_name' => $request->last_name ?? null,
                                'action' => EnquiryActionEnum::RegistrationFailed_EstimatedEvent
                            ]);
                        }
                    }

                    return (object) [
                        'status' => false,
                        'message' => 'You can\'t register for an estimated event.'
                    ];
                }
            } else {
                return (object) [
                    'status' => false,
                    'message' => 'The event is not active.'
                ];
            }
        } else {
            // TODO: Notify the admin and developers about this
            Log::channel(static::getSite()?->code . 'adminanddeveloper')->debug('Registration attempt to deleted or drafted event: ' . json_encode($this->load('event')));

            return (object) [
                'status' => false,
                'message' => 'The event is not active.'
            ];
        }

        return (object) [
            'status' => true,
            'message' => 'The event registrations are still active.'
        ];
    }

    /**
     * Check if the event event category has available places.
     * 
     * @param  Request|null  $request
     * @param  Charity|null  $charity
     * @return object
     */
    public function _hasAvailablePlaces(?Request $request = null, ?Charity $charity = null): object
    {
        if ($charity) { // Check if the charity has available places for the given event & event category
            $hasAvailablePlaces = $this->charityHasAvailablePlaces($charity, $request);
        } else { // Check if the event has available places for the given event category
            $hasAvailablePlaces = $this->hasAvailablePlaces($request);
        }

        return $hasAvailablePlaces;
    }

    /**
     * Check if the event has available places in the given event category. This is mostly for registrations that do not require a charity. For those that require a charity, please check the charityHasAvailablePlaces method
     *
     * @param  Request|null  $request
     * @return object
     */
    public function hasAvailablePlaces(?Request $request = null): object
    {
        if ($this->total_places) {
            // Get count of participants that have completed their entry in this eec
            $completeParticipants = $this->participants()
                ->consideredAmongCompletedRegistration()
                ->count();

            $remaining = $this->total_places - $completeParticipants;

            if (!(app()->runningInConsole() && SiteEnum::belongsToOrganisation(OrganisationEnum::GWActive))) { // Keep offering places to LDT participants after total_places have been exhausted
                if ($remaining < 1) {
                    if ($request && (! Auth::check() || AccountType::isParticipant()) && ! app()->runningInConsole()) { // The request should not be authenticated or the authenticated user's active role should be participant
                        if ($request->email) {
                            Enquiry::updateOrCreate([
                                'site_id' => $this->site?->id,
                                'event_id' => $this->event_id,
                                'event_category_id' => $this->event_category_id,
                                'email' => $request->email,
                                'charity_id' => Charity::where('ref', $request->charity)->value('id'),
                            ], [
                                'first_name' => $request->first_name ?? null,
                                'last_name' => $request->last_name ?? null,
                                'action' => EnquiryActionEnum::RegistrationFailed_EventPlacesExhausted
                            ]);

                            CacheDataManager::flushAllCachedServiceListings(new EnquiryDataService);
                        }
                    }

                    return (object) [
                        'status' => false,
                        'places' => 0,
                        'message' => 'There are no available registration places for the event under this category.'
                    ];
                }
            }
        }

        return (object) [
            'status' => true,
            'places' => $remaining ?? 0,
            'message' => 'The event has available places in this category.'
        ];
    }

    /**
     * Check if the charity has available places for an event.
     * Only check this if the registration is associated with a charity.
     *
     * @param  Charity       $charity
     * @param  Request|null  $request
     * @return object
     */
    public function charityHasAvailablePlaces(Charity $charity, ?Request $request = null): object
    {
        $hasAvailablePlaces = $this->hasAvailablePlaces($request); // Check whether eec has available places

        if ($hasAvailablePlaces->status) { // Check if the charity has available places for the given event and event category
            $isCharityOwnerOrUser = AccountType::isCharityOwnerOrCharityUser(); // Used to customize the message returned

            if (! $charity->hasActiveMembership()) { // Check if charity has an active membership
                return (object) [
                    'status' => false,
                    'places' => 0,
                    'message' => $isCharityOwnerOrUser
                        ? 'Your charity does not have an active membership.'
                        : 'The charity does not have an active membership.'
                ];
            }

            $charityAvailablePlaces = $this->placesAvailableToCharityMembership($charity); // Check if the charity has available places

            // TODO: Update the event_id on the market resale related tables to event_event_category_id
            $soldPlaces = ResalePlace::charitySoldPlaces($charity, $this->event);
            $boughtPlaces = ResalePlace::charityBoughtPlaces($charity, $this->event);

            $charityAvailablePlaces -= $soldPlaces;
            $charityAvailablePlaces += $boughtPlaces;

            if ($charityAvailablePlaces < 1) {
                if ($request && (! Auth::check() || AccountType::isParticipant()) && ! app()->runningInConsole()) { // The request should not be authenticated or the authenticated user's active role should be participant
                    if ($request->email) {
                        Enquiry::updateOrCreate([
                            'site_id' => $this->site?->id,
                            'event_id' => $this->event_id,
                            'event_category_id' => $this->event_category_id,
                            'email' => $request->email,
                            'charity_id' => Charity::where('ref', $request->charity)->value('id'),
                        ], [
                            'first_name' => $request->first_name ?? null,
                            'last_name' => $request->last_name ?? null,
                            'action' => EnquiryActionEnum::RegistrationFailed_CharityPlacesExhausted
                        ]);
                    }
                }

                return (object) [
                    'status' => false,
                    'places' => $charityAvailablePlaces,
                    'message' => $isCharityOwnerOrUser
                        ? 'Your charity has filled all their available places for this event under this category.'
                        : 'The charity has filled all their available places for this event under this category.'
                ];
            }

            return (object) [
                'status' => true,
                'places' => $charityAvailablePlaces,
                'message' => $isCharityOwnerOrUser
                    ? 'Your charity has available places for this event under this category.'
                    : 'The charity has available places for this event under this category.'
            ];
        }

        return $hasAvailablePlaces;
    }

    /**
     * Get the number of places (registrations) a charity has for an event (in the given event category) based on the number of places (seats) allocated to it's charity membership type for the event.
     *
     * @param  Charity  $charity
     * @return int
     */
    public function placesAllocatedToCharityMembership(Charity $charity): int
    {
        switch ($charity->latestCharityMembership->type) {
            case CharityMembershipTypeEnum::Classic:
                $places = $this->classic_membership_places;
                break;

            case CharityMembershipTypeEnum::Premium:
                $places = $this->premium_membership_places;
                break;

            case CharityMembershipTypeEnum::TwoYear:
                $places = $this->two_year_membership_places;
                break;

            case CharityMembershipTypeEnum::Partner:
                $places = (int) Setting::where('site_id', $this->site?->id)->first()?->settingCustomFields()->where('key', 'partner_membership_default_places')->value('value'); // Get the partner_membership_default_places for the site making the request
                break;

            default:
                $places = 0;
        }

        return (int) $places;
    }

    /**
     * Get the available places (registrations) a charity has for an event (in the given event category) based on the number of places (seats) allocated to it's charity membership type for the event.
     *
     * @param  Charity  $charity
     * @return int
     */
    public function placesAvailableToCharityMembership(Charity $charity): int
    {
        if ($places = $this->placesAllocatedToCharityMembership($charity)) {
            $completeParticipants = $this->participants() // Get a count of participants that have registered for this event & event category under the given charity
                ->consideredAmongCompletedRegistration($charity)        // TODO: Check whether to add some constraints like status=paid,complete,clearance; and invoice to this query
                ->count();

            if ($charity->latestCharityMembership->type == CharityMembershipTypeEnum::Partner) {
                if (Setting::where('site_id', $this->site?->id)->first()?->settingCustomFields()->where('key', 'partner_membership_default_places')->value('type')?->value == SettingCustomFieldTypeEnum::AllEvents->value) {
                    $completeParticipants = $charity->participants()->whereHas('event', function($query) { // Partner charities are only entitled to a defined number of places (set by each site working with charities) for all events on our system and this is usually 1 place. NB: This logic is written to handle the case where the partner charity could have a defined number of places per event.
                        $query->whereHas('eventCategories', function ($query) {                         // Overide the $completeParticipants value with this query.
                            $query->whereYear('start_date', '=', Carbon::now()->year);
                        });
                    })->count();
                }
            }

            $remainingPlaces = $places - $completeParticipants;

            return $remainingPlaces > 0 ? $remainingPlaces : 0;
        } else { // Throw an exception when no places are allocated to charity membership
            $message = AccountType::isCharityOwnerOrCharityUser() || AccountType::isAdmin() // Customize the message returned
                ? "No places are allocated to " . (AccountType::isAdmin() ? "the " . $charity->latestCharityMembership->type->value : "your") . " charity membership type for this event."
                : "No places are allocated to the charity for this event.";

            throw new Exception($message);
        }

        return 0;
    }

    /**
     * Get the price range
     * 
     * @param  ?Model    $property
     * @param  ?Request  $request
     * @return array
     */
    public static function priceRange(?Model $property = null, ?Request $request = null): array
    {
        return (new CacheDataManager(
            new EventClientDataService(),
            'getPriceRange',
            [$property, $request]
        ))->extraKey('price-range')->getData();
    }

    /**
     * Ensure the fee is valid for checkout through stripe as stripe requires the amount to be at least £0.30 gbp
     *
     * @return object
     */
    public function isFeeValidForCheckout(): object
    {
        // TODOL: @tsaffi - Ensure to consider the type of fee (local or international) the participant is expected to pay
        if ($this->local_fee && $this->local_fee < 1) {
            // Notify admin and developers about this
            Log::channel(static::getSite()?->code . 'adminanddeveloper')->debug("The local fee ($this->local_fee) for {$this->event->formattedName} ({$this->eventCategory?->name}) is less than £1. As such, it is impossible to checkout through stripe.");

            return (object) [
                'status' => false,
                'message' => 'The local fee should not be less than £1.'
            ];
        }

        if ($this->international_fee && $this->international_fee < 1) {
            // Notify admin and developers about this
            Log::channel(static::getSite()?->code . 'adminanddeveloper')->debug("The international fee ($this->international_fee) for {$this->event->formattedName} ({$this->eventCategory?->name}) is less than £1. As such, it is impossible to checkout through stripe.");

            return (object) [
                'status' => false,
                'message' => 'The international fee should not be less than £1.'
            ];
        }

        return (object) [
            'status' => true,
            'message' => 'The fee is valid for checkout.'
        ];
    }
}
