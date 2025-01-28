<?php

namespace App\Modules\Charity\Models;

use Auth;
use App\Mail\Mail;
use Carbon\Carbon;
use App\Http\Helpers\AccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Contracts\CanHaveManySearchHistories;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Contracts\Metables\CanHaveMetableResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\Charity\Models\Relations\CharityRelations;
use App\Contracts\Socialables\CanHaveManySocialableResource;
use App\Contracts\Uploadables\CanHaveManyUploadableResource;
use App\Contracts\Locationables\CanHaveLocationableResource;
use App\Contracts\Invoiceables\CanHaveManyInvoiceableResource;
use App\Contracts\InvoiceItemables\CanHaveManyInvoiceItemableResource;

use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;

use App\Traits\SiteTrait;
use App\Traits\SlugTrait;
use App\Traits\Metable\HasOneMeta;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Traits\Socialable\HasManySocials;
use App\Traits\Uploadable\HasManyUploads;
use App\Traits\Invoiceable\HasManyInvoices;
use App\Traits\Locationable\HasOneLocation;
use App\Traits\InvoiceItemable\HasManyInvoiceItems; // Don't link an invoice to a charity directly. This relationship was made due to the seeded data (from to the previous structure of the database) as some invoices could not be linked to the specific ResaleRequest (market_resale) or CharityMembership (charity_membership) record it was created for.
use App\Modules\Charity\Models\Traits\CharityQueryScopeTrait;

use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\CallNoteStatusEnum;
use App\Enums\CampaignStatusEnum;
use App\Enums\EventCharitiesEnum;
use App\Enums\CharityUserTypeEnum;
use App\Enums\CampaignPackageEnum;
use App\Enums\CharityEventTypeEnum;
use App\Enums\CharityEENSettingsEnum;
use App\Enums\CharityMembershipTypeEnum;
use App\Enums\CharityCompleteNotificationsEnum;
use App\Traits\HasManySearchHistories;
use App\Traits\UseDynamicallyAppendedAttributes;
use Laravel\Scout\Searchable;

class Charity extends Model implements CanUseCustomRouteKeyName, CanHaveManySearchHistories, CanHaveManyUploadableResource, CanHaveManySocialableResource, CanHaveMetableResource, CanHaveLocationableResource, CanHaveManyInvoiceableResource, CanHaveManyInvoiceItemableResource
{
    use Searchable {
        search as parentSearch;
    }
    use HasFactory,
        SlugTrait,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SoftDeletes,
        SiteTrait,
        CharityQueryScopeTrait,
        HasManyUploads,
        HasManySocials,
        HasOneMeta,
        HasManyInvoices,
        HasManyInvoiceItems,
        CharityRelations,
        HasOneLocation,
        UseDynamicallyAppendedAttributes,
        HasManySearchHistories;

    protected $table = 'charities';

    protected $fillable = [
        'charity_category_id',
        'status',
        'name',
        'email',
        'phone',
        'postcode',
        'city',
        'country',
        'primary_color',
        'secondary_color',
        'website',
        'supporters_video',
        'donation_link',
        'show_in_external_feeds',
        'show_in_vmm_external_feeds',
        'external_strapline',
        'charity_checkout_id',
        'charity_checkout_integration',
        'fundraising_emails_active',
        'complete_notifications',
        'external_enquiry_notification_settings',
        'fundraising_platform',
        'fundraising_platform_url',
        'fundraising_ideas_url',
        'finance_contact_name',
        'finance_contact_email',
        'finance_contact_phone',
        'manager_call_notes',
        'manager_call_status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'show_in_external_feeds' => 'boolean',
        'show_in_vmm_external_feeds' => 'boolean',
        'charity_checkout_integration' => 'boolean',
        'fundraising_emails_active' => 'boolean',
        'complete_notifications' => CharityCompleteNotificationsEnum::class,
        'external_enquiry_notification_settings' => CharityEENSettingsEnum::class,
        'manager_call_status' => CallNoteStatusEnum::class
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $appends = [];

    const ACTIVE = 1; // Active charity

    const INACTIVE = 0; // InActive charity

    /**
     * @return string
     */
    public function getRef(): string
    {
        return $this->attributes['ref'];
    }

    /**
     * Get the charity's name.
     * TODO: Confirm this charity name customization based on the website from @Fru and improve on this logic.
     *
     * @return Attribute
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value == "4 cancer group") {
                    $domain = static::getSite()?->domain;

                    if ($domain) {
                        switch ($domain) {
                            case SiteEnum::SportForCharity:
                                return "Sail 4 Cancer";
                                break;
                            case SiteEnum::RunForCharity:
                                return "Run 4 Cancer";
                                break;
                            case SiteEnum::CycleForCharity:
                                return "Bike 4 Cancer";
                                break;
                        }
                    }
                }

                return ucfirst($value);
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
                return static::getSite()?->url . '/charities/' . $this->slug;
            },
        );
    }

    /**
     * Get the charity's donation link.
     * TODO: Confirm this charity donation link customization based on the website from @Fru and improve on this logic.
     *
     * @return Attribute
     */
    protected function donationLink(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value == "http://www.run4cancer.org/DonationRun4Cancer") {
                    $domain = static::getSite()?->domain;

                    if ($domain) {
                        switch ($domain) {
                            case SiteEnum::SportForCharity:
                                return "http://www.run4cancer.org/DonationRun4Cancer";
                                break;
                            case SiteEnum::RunForCharity:
                                return "http://www.run4cancer.org/DonationRun4Cancer";
                                break;
                            case SiteEnum::CycleForCharity:
                                return "http://www.bike4cancer.org/DonationBike4Cancer";
                                break;
                        }
                    }
                }

                return $value;
            },
        );
    }

    /**
     * Determine donations credit balance.
     *
     * @return Attribute
     */
    public function creditBalance(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $donated = 0;

                foreach ($this->donations as $donation) {
                    if ($donation->status) {
                        $donated += ($donation->amount * $donation->conversion_rate);
                    }
                }

                return round($donated);
            }
        );
    }

    /**
     * Get credit balance in pounds.
     *
     * @return Attribute
     */
    public function creditBalanceInPounds(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->credit_balance / config('corporate.credit.rate');
            }
        );
    }

    /**
     * Get the time passed or remaining before the membership expiry date.
     *
     * @return Attribute
     */
    public function renewal(): Attribute
    {
        return Attribute::make(
            get: function () {
                $renewal = [
                    'difference' => null,
                    'info' => null,
                    'state' => null,
                    'label' => null
                ];

                $now = Carbon::now();
                $expiryDate = $this->latestCharityMembership?->expiry_date ? Carbon::parse($this->latestCharityMembership->expiry_date) : null;

                if ($expiryDate && $expiryDate->lessThan($now)) {
                    $diff = $now->diffInMonths($expiryDate);
                    $diffInYears = $now->diffInYears($expiryDate);

                    $renewal['difference'] = $diff;
                    $renewal['info'] = ' | ' . ($diff == 0 ? '' : '+') . $diff . ($diff == 1 ? ' Month' : ' Months');

                    $overdueYears = round($diffInYears / ($this->latestCharityMembership->type == CharityMembershipTypeEnum::TwoYear ? 2 : 1));

                    $renewal['overdue'] = ($this->latestCharityMembership->membership_fee + ($overdueYears * $this->latestCharityMembership->membership_fee));
                } else if ($expiryDate && $expiryDate->greaterThan($now)) {
                    $diff = $now->diffInMonths($expiryDate);
                    $renewal['difference'] = $diff;
                    $renewal['info'] = ' | ' . ($diff == 0 ? '' : '-') . $diff . ($diff == 1 ? ' Month' : ' Months');
                }

                if ($this->latestCharityMembership?->renewed_on) {
                    $renewedOn = Carbon::parse($this->latestCharityMembership->renewed_on);

                    if ($renewedOn->isSameMonth($now)) {
                        if ($this->latestCharityMembership->type == $this->previousCharityMembership?->type) {
                            if ($this->latestCharityMembership->use_new_membership_fee) {
                                $renewal['state'] = 'Upgraded';
                                $renewal['label'] = 'label label-warning';
                            } else {
                                $renewal['state'] = 'Renewed';
                                $renewal['label'] = 'label label-success';
                            }
                        } else {
                            if ($this->latestCharityMembership->type == CharityMembershipTypeEnum::TwoYear) {
                                $renewal['state'] = 'Upgraded';
                                $renewal['label'] = 'label label-warning';
                            } elseif ($this->latestCharityMembership->type == CharityMembershipTypeEnum::Premium) {
                                if ($this->previousCharityMembership?->type == CharityMembershipTypeEnum::TwoYear) {
                                    $renewal['state'] = 'Downgraded';
                                    $renewal['label'] = 'label label-danger';
                                } else {
                                    $renewal['state'] = 'Upgraded';
                                    $renewal['label'] = 'label label-warning';
                                }
                            } elseif ($this->latestCharityMembership->type == CharityMembershipTypeEnum::Classic) {
                                if ($this->previousCharityMembership?->type == CharityMembershipTypeEnum::TwoYear || $this->previousCharityMembership?->type == CharityMembershipTypeEnum::Premium) {
                                    $renewal['state'] = 'Downgraded';
                                    $renewal['label'] = 'label label-danger';
                                } else {
                                    $renewal['state'] = 'Upgraded';
                                    $renewal['label'] = 'label label-warning';
                                }
                            } else {
                                $renewal = [
                                    'state' => 'Downgraded',
                                    'label' => 'label label-danger'
                                ];
                            }
                        }
                    } else {
                        $renewal['state'] = 'Renewal';
                        $renewal['label'] = 'label label-primary';
                    }
                } else {
                    $renewal['state'] = 'Renewal';
                    $renewal['label'] = 'label label-primary';
                }

                return $renewal;
            }
        );
    }

    /**
     * Check whether the charity has an active membership.
     *
     * @return bool
     */
    public function hasActiveMembership(): bool
    {
        $this->load('latestCharityMembership');

        if ($this->latestCharityMembership) {
            return $this->latestCharityMembership->status && Carbon::parse($this->latestCharityMembership->expiry_date)->greaterThan(Carbon::now());
        }

        return false;
    }

    /**
     * Assign a user with the relationship type (owner, manager, user, participant) to a charity.
     *
     * @param  User                 $user
     * @param  CharityUserTypeEnum  $type
     * @return void
     */
    public function assignToUser(User $user, CharityUserTypeEnum $type): void
    {
        $this->users()->attach($user->id, ['type' => $type]);
    }

    /**
     * Assign an account manager to a charity.
     * NB: Charities are currently managed by only one account manager.
     *
     * @param  User  $user
     * @return void
     */
    public function assignToAccountManager(User $user): void
    {
        $this->users()->wherePivot('type', CharityUserTypeEnum::Manager)?->detach(); // Remove all account managers associated with the charity (to ensure every charity has only one account manager).

        $this->assignToUser($user, CharityUserTypeEnum::Manager);
    }

    /**
     * Replaces the hasPlaces method (with some improvements).
     * Checks whether the charity is among the charities allowed to run an event (Events that require to be run only by some specific/selected charities).
     *
     * @param  Charity  $charity
     * @param  Event    $event
     * @return bool
     */
    public static function isAllowed(Charity $charity, Event $event): bool
    {
        if ($event->charities == EventCharitiesEnum::Included) {
            return CharityEvent::where('charity_id', $charity->id)
                ->where('event_id', $event->id)
                ->where('type', CharityEventTypeEnum::Included)
                ->first() ? true : false;
        } else if ($event->charities == EventCharitiesEnum::Excluded) {
            return CharityEvent::where('charity_id', $charity->id)
                ->where('event_id', $event->id)
                ->where('type', CharityEventTypeEnum::Excluded)
                ->first() ? false : true;
        }

        return true;
    }

    // /**
    //  * Get the supporters_video_id attribute.
    //  */
    // public function getSupportersVideoIdAttribute()
    // {
    //     if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $this->supporters_video, $match)) {
    //         return $match[1];
    //     } else {
    //         return $this->supporters_video;
    //     }
    // }

    /**
     * Add credits to the charity.
     *
     * @return void
     */
    public function addCredits(): void
    {
        $donation = new Donation();
        $donation->charity_id = $this->id;

        switch ($this->latestCharityMembership->type) {
            case CharityMembershipTypeEnum::TwoYear:
                $donation->amount = 400;
                break;

            case CharityMembershipTypeEnum::Premium:
                $donation->amount = 200;
                break;

            case CharityMembershipTypeEnum::Classic:
                $donation->amount = 100;
                break;

            default:
                $donation->amount = 0;
                break;
        }

        $donation->conversion_rate = config('corporate.credit.rate');
        $donation->expires_at = $this->latestCharityMembership->expiry_date;
        $donation->save();
    }

    /**
     * Create a campaign for the charity.
     *
     * @return void
     */
    public function createCampaign(): void
    {
        $user = User::where('email', config('mail.ldt_em_email.address'))->whereHas('roles', function ($query) {
            $query->where('title', 'event_manager');
        })->first();

        $campaign = new Campaign();
        $campaign->charity_id = $this->id;
        $campaign->user_id = $user?->id;
        $campaign->start_date = $this->previousCharityMembership?->expiry_date;
        // $campaign->start_date = $this->previousCharityMembership?->expiry_date ?: Carbon::now(); // Should the line above be updated to this, so the the start_date of the campaign is the current date in case the Charity does not have a previousMembership (that is, it the first time it subscribes to a membership)
        $campaign->end_date = $this->latestCharityMembership->expiry_date;
        $campaign->status = Carbon::now()->greaterThanOrEqualTo(Carbon::parse($campaign->start_date)) ? CampaignStatusEnum::Active : CampaignStatusEnum::Created;

        switch ($this->latestCharityMembership->type) {
            case CharityMembershipTypeEnum::TwoYear:
                $campaign->title = $this->name . ' - 2 Year Campaign';
                $campaign->package = CampaignPackageEnum::Leads_100;
                break;

            case CharityMembershipTypeEnum::Premium:
                $campaign->title = $this->name . ' - Premium Campaign';
                $campaign->package = CampaignPackageEnum::Leads_50;
                break;

            case CharityMembershipTypeEnum::Classic:
                $campaign->title = $this->name . ' - Classic Campaign';
                $campaign->package = CampaignPackageEnum::Leads_25;
                break;
        }

        $campaign->save();

        $campaign->load('charity', 'user');

        $data['campaign'] = $campaign->toArray();

        // TODO: Update this and send the email to the Event Manager

        /*if($data['campaign']['manager']) { // Notify the campaign manager about the details of the campaign
            Mail::site()->queue('emails.campaign-created', $data, function($message) use ($data) {
                $message->from(env('MAIL_FROM_ADDRESS', 'noreply@sportforcharity.com'), env('MAIL_FROM_NAME', 'Sport For Charity'));
                $message->to($data['campaign']['manager']['email']);
                $message->subject("New Campaign Created For ".$data['campaign']['charity']['title']);
            });
        }*/
    }

    /**
     * Get the percentage at which the charity profile is filled.
     *
     * @param Charity  $charity
     * @return int
     */
    public static function percentComplete(Charity $charity): int
    {
        $charity = $charity->unsetRelations()->toArray();

        $removeKeys = array('id', 'status', 'email', 'created_at', 'updated_at', 'deleted_at');

        foreach ($removeKeys as $key) {
            unset($charity[$key]);
        }

        $fieldCount = count($charity);

        $filled = 0;

        foreach ($charity as $value) {
            if (isset($value)) {
                $filled++;
            }
        }

        try {
            $percent = ($filled / $fieldCount) * 100;

            if ($percent > 100) $percent = 100;
        } catch (\ErrorException $e) {
            $percent = 0;
        }

        $percent = (int) $percent;

        return $percent;
    }

    // /**
    //  * TODO: Revise this after working on the Participant & Event Modules
    //  *
    //  * @return string
    //  */
    // public function getTotalCost(): string
    // {
    //     $totalCost = 0;

    //     foreach($this->participants->groupBy('event_id') as $participants) {
    //         $places = $participants->count();
    //         $cost = ($participants[0]['event']['local_fee'] * $places);
    //         $totalCost += $cost;
    //     }

    //     return number_format($totalCost, 2);
    // }

    // /**
    //  * Get the event place quarterly statement for a business period.
    //  * TODO: Revise this after working on the Participant & Event Modules
    //  * @param array $period
    //  * @return Charity
    //  */
    // public function getQuarterlyStatement(array $period): Charity
    // {
    //     $charity = $this::with(['participants' => function($query) use ($period) {
    //         $query->with('event');
    //         $query->with('invoice');
    //         $query->where('status', 'complete'); // Get all complete participants

    //         $query->whereHas('event', function($q) use ($period) {
    //             $q->where(function($q1) use ($period) { // Include in report, events whose registration deadline has passed in the period
    //                 $q1->whereNotNull('registration_deadline');
    //                 $q1->whereBetween('registration_deadline', $this->getStartAndEndDatesFromPeriod($period));
    //                 $q1->orWhere(function($q2) { // If the event has no registration deadline OR if it's a rolling event, include in the report its places bought during the period
    //                     $q2->whereNull('registration_deadline');
    //                     $q2->orWhere('rolling_event', 1);
    //                 });
    //             });
    //         });
    //     }]);

    //     if (AccountType::isAccountManager()) {
    //         $charity = $charity->whereHas('charityManager', function($query) {
    //             $query->where('user_id', Auth::user()->id);
    //         });
    //     }

    //     $charity = $charity->firstOrFail();

    //     $charity->participants = $charity->participants->filter(function($participant) use ($period) {
    //         if (!$participant->event->registration_deadline || $participant->event->rolling_event) { // If the event has no registration deadline OR if it's a rolling event, make sure the participant joined the event during the summary period
    //             return $period['startDate']->lessThanOrEqualTo(Carbon::parse($participant->created_at)) && $period['endDate']->greaterThanOrEqualTo(Carbon::parse($participant->created_at));
    //         }

    //         return true;
    //     });

    //     return $charity;
    // }

    // /**
    //  * Get the event place quarterly statement summary.
    //  * The participants here are those that have completed their registration (look at the Charity getEventPlaceQuarterlyStatement() method).
    //  * TODO: Revise this after working on the Participant & Event Modules
    //  * @return array
    //  */
    // public function getQuarterlyStatementSummary(): array
    // {
    //     $summary = [
    //         'totalCost' => 0,
    //         'totalOutstandingCost' => 0,
    //         'totalExemptedCost' => 0,
    //         'totalPartialParticipantCost' => 0,
    //         'totalPartialCharityCost' => 0,
    //         'totalOtherCost' => 0,
    //         'totalPlaces' => 0,
    //         'totalExemptedPlaces' => 0,
    //         'totalExemptedPartialPlaces' => 0,
    //         'totalOtherPlaces' => 0,
    //     ];

    //     foreach ($this->participants->groupBy('event_id') as $eventId => $participants) {
    // $exemptedPlaces = $participants->where('exempted', ParticipantExemptedEnum::Full)->count();
    // $partialPlaces =  $participants->where('exempted', ParticipantExemptedEnum::Partial)->count();
    //         $exemptedPartialPlaces = $exemptedPlaces + $partialPlaces;
    //         $otherPlaces = $participants->where('exempted', ParticipantExemptedEnum::No)->where('exempted', ParticipantExemptedEnum::No)->count();
    //         $places = $participants->count();

    //         $summary['totalExemptedPlaces'] += $exemptedPlaces;
    //         $summary['totalExemptedPartialPlaces'] += $exemptedPartialPlaces;
    //         $summary['totalOtherPlaces'] += $otherPlaces;
    //         $summary['totalPlaces'] += $places;

    //         $exemptedCost = ($participants[0]['event']['local_fee'] * $exemptedPlaces);
    //         $partialCost = ($participants[0]['event']['local_fee'] * $partialPlaces);
    //         $partialParticipantCost = 0;

    //         foreach($participants as $participant) {
    // if ($participant->invoice && $participant->exempted == ParticipantExemptedEnum::Partial) {
    //                 $chargeRate = ((float) env('PARTICIPANT_REGISTRATION_CHARGE_RATE')) + 100;
    //                 $rate = $chargeRate / 100;
    //                 $cost = $participant->invoice->price / $rate;

    //                 $partialParticipantCost += round($cost, 2);
    //             }
    //         }

    //         $partialCharityCost = $partialCost - $partialParticipantCost;
    //         $otherCost = ($participants[0]['event']['local_fee'] * $otherPlaces);
    //         $cost = ($participants[0]['event']['local_fee'] * $places);
    //         $outstandingCost = $exemptedCost + $partialCharityCost;

    //         $summary['totalExemptedCost'] += $exemptedCost;
    //         $summary['totalPartialParticipantCost'] += $partialParticipantCost;
    //         $summary['totalPartialCharityCost'] += $partialCharityCost;
    //         $summary['totalOtherCost'] += $otherCost;
    //         $summary['totalCost'] += $cost;
    //         $summary['totalOutstandingCost'] += $outstandingCost;

    //         $summary['events'][$eventId]['id'] = $eventId;
    //         $summary['events'][$eventId]['eventTitle'] = $participants[0]->event->title;
    //         $summary['events'][$eventId]['eventPrice'] = $participants[0]->event->local_fee;
    //         $summary['events'][$eventId]['exemptedPartialPlaces'] = $exemptedPartialPlaces;
    //         $summary['events'][$eventId]['exemptedCost'] = $exemptedCost;
    //         $summary['events'][$eventId]['otherPlaces'] = $otherPlaces;
    //         $summary['events'][$eventId]['otherCost'] = $otherCost;
    //         $summary['events'][$eventId]['partialParticipantCost'] = $partialParticipantCost;
    //         $summary['events'][$eventId]['partialCharityCost'] = $partialCharityCost;
    //         $summary['events'][$eventId]['places'] = $places;
    //         $summary['events'][$eventId]['cost'] = $cost;
    //         $summary['events'][$eventId]['outstandingCost'] = $outstandingCost;
    //     }

    //     // format the cost
    //     $summary['totalCost'] = number_format($summary['totalCost'], 2);
    //     $summary['totalOutstandingCost'] = number_format($summary['totalOutstandingCost'], 2);
    //     $summary['totalExemptedCost'] = number_format($summary['totalExemptedCost'], 2);
    //     $summary['totalPartialParticipantCost'] = number_format($summary['totalPartialParticipantCost'], 2);
    //     $summary['totalPartialCharityCost'] = number_format($summary['totalPartialCharityCost'], 2);
    //     $summary['totalOtherCost'] = number_format($summary['totalOtherCost'], 2);

    //     return $summary;
    // }

    /**
     * Get all the active charities.
     *
     * @return Collection
     */
    public static function listAll(): Collection
    {
        $charities = Charity::status(static::ACTIVE)
            ->filterByAccess();

        $charities = $charities->orderBy('name', 'ASC')->get();

        return $charities;
    }

    /**
     * List all the charities allowed to be showed in vmm external feeds (That is, having the show_in_vmm_external_feeds set to true).
     *
     * @return Collection
     */
    public static function listAllVMM(): Collection
    {
        $charities = Charity::where('status', static::ACTIVE)
            ->where('show_in_vmm_external_feeds', static::ACTIVE);

        if (AccountType::isAccountManager()) {
            $charities = $charities->whereHas('charityManager', function ($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        if (AccountType::isCharityOwnerOrCharityUser()) {
            $charities = $charities->whereHas('users', function ($query) {
                $query->where('user.id', Auth::user()->id);
            });
        }

        $charities = $charities->orderBy('name', 'ASC')->get();

        return $charities;
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
            // 'charity_categories.name' => ''
        ];
    }

    /**
     * Override the default Laravel scout search method.
     *
     * @param  string $query
     * @param  \Closure  $callback
     * @return \Laravel\Scout\Builder
     */
    public static function search($query = '', $callback = null): \Laravel\Scout\Builder
    {
        return static::parentSearch($query, $callback)->query(
            function ($builder) {
                $builder->join('charity_categories', 'charities.charity_category_id', '=', 'charity_categories.id')
                    ->join('charity_memberships', 'charities.id', '=', 'charity_memberships.charity_id')
                    ->where('charities.status', static::ACTIVE)
                    ->where('charity_memberships.status', static::ACTIVE)
                    ->select('charities.id', 'charities.ref', 'charities.name', 'charities.slug')
                    ->orderBy('charities.name');
            }
        );
    }
}
