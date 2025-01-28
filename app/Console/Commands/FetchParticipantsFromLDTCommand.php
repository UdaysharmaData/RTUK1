<?php

namespace App\Console\Commands;

use Str;
use Exception;
use App\Mail\Mail;
use Carbon\Carbon;
use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\GenderEnum;
use App\Modules\Setting\Enums\SiteCodeEnum;
use App\Enums\RoleNameEnum;
use App\Enums\BoolYesNoEnum;
use App\Jobs\ResendEmailJob;
use Illuminate\Console\Command;
use App\Modules\User\Models\User;
use App\Mail\NewLDTRegistrations;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Enums\ProfileEthnicityEnum;
use Illuminate\Support\Facades\Http;
use App\Modules\Setting\Models\Site;
use App\Enums\PredefinedPartnersEnum;
use App\Modules\Charity\Models\Charity;
use App\Modules\Partner\Models\Partner;
use App\Enums\PredefinedPartnerChannelEnum;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Jobs\OfferPlacesToLDTParticipantsJob;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Partner\Models\PartnerChannel;
use App\Modules\Event\Models\EventThirdParty;
use Illuminate\Http\Client\ConnectionException;
use App\Enums\ParticipantProfileWeeklyPhysicalActivityEnum;
use App\Modules\Setting\Enums\OrganisationEnum;

class FetchParticipantsFromLDTCommand extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'external-enquiry:fetch-from-ldt {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch participants from LDT and store them as external enquiries.';

    /**
     * Execute the console command.
     * Daily fetch the last external enquiry. Then, use it's channel_record_id to fetch all new LDT participants (per the event) after it.
     * If no [last] external enquiry was found, it means it's either the first time we're fetch participants for that event or the event has no participants on LDT. Regardless, fetch all participants of the event from LDT.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $site = Site::where('name', $this->argument('site'))
                ->orWhere('domain', $this->argument('site'))
                ->orWhere('code', $this->argument('site'))
                ->firstOrFail();

            Log::channel($site->code . 'ldtfetch')->info('Fetch participants from LDT Start');

            $data = [];

            Event::active(true)
                ->with(['eventThirdParties' => function ($query) use ($site) {
                    $query->with('eventCategories')
                        ->whereNotNull('external_id')
                        ->whereHas('partnerChannel', function ($query) use ($site) {
                            $query->whereHas('partner', function ($query) use ($site) {
                                $query->where('site_id', $site->id)
                                    ->where('code', PredefinedPartnersEnum::LetsDoThis->value);
                            });
                        });
                }])->whereHas('eventThirdParties', function ($query) use ($site) {
                    $query->whereNotNull('external_id')
                        ->whereHas('partnerChannel', function ($query) use ($site) {
                            $query->whereHas('partner', function ($query) use ($site) {
                                $query->where('site_id', $site->id)
                                    ->where('code', PredefinedPartnersEnum::LetsDoThis->value);
                            });
                        })->has('eventCategories');
                })->whereHas('eventCategories', function ($query) use ($site) { // Get events the site has access to
                    $query->whereHas('site', function ($query) use ($site) {
                        $query->where('domain', $site->domain);
                    });
                })->chunk(1, function ($events) use ($data, $site) {
                    foreach ($events as $event) {
                        $headers = [
                            'Content-Type' => 'application/json'
                        ];

                        $eventThirdParty = $event->eventThirdParties[0];

                        foreach ($eventThirdParty->eventCategories as $category) {
                            Log::channel($site->code . 'ldtfetch')->info('Another one!');
                            Log::channel($site->code . 'ldtfetch')->info('Event Category: '. json_encode(collect($category->toArray())->only(['id', 'name'])->all()));
                            Log::channel($site->code . 'ldtfetch')->info('Event: '. json_encode(collect($event->toArray())->only(['id', 'name'])->all()));

                            if ($category->pivot->external_id) {
                                $queryParams = [
                                    'page' => [
                                        'size' => 1000 // default is 100
                                    ]
                                ];

                                if (! SiteEnum::isMainSiteInOrganization(OrganisationEnum::GWActive, SiteEnum::from($site->domain))) { // Only fetch records belonging to the given origin when the site is not the main site in the GWActive organisation
                                    $queryParams = [
                                        ...$queryParams,
                                        'origin' => $site->code
                                    ];
                                }
                                if ($eventThirdParty->occurrence_id) { // Only set the occurrence_id in the query param when it's not null
                                    $queryParams = [
                                        ...$queryParams,
                                        'eventOccurrenceId' => $eventThirdParty->occurrence_id,
                                    ];
                                }
                                $latestEntry = ExternalEnquiry::where('event_id', $event->id)
                                    ->where('event_category_event_third_party_id', $category->pivot->id)
                                    ->latest()
                                    ->orderByDesc('id')
                                    ->first();

                                if ($latestEntry) {
                                    $queryParams = [
                                        ...$queryParams,
                                        'page' => [
                                            ...$queryParams['page'],
                                            'after' => $latestEntry->channel_record_id
                                        ]
                                    ];
                                }

                                $participantCount = 0;

                                do {
                                    $externalEnquiries = [];
                                    $extraData = [];

                                    $url = config('apiclient.ldt_endpoint')."{$category->pivot->external_id}/participants?".http_build_query($queryParams);

                                    $result = Http::withHeaders($headers)
                                        ->withToken(config('apiclient.ldt_token'))
                                        ->accept('application/json')
                                        ->retry(3, 100, function ($exception, $request) use ($site) { // Only retry the request if the initial request encounters an ConnectionException
                                            Log::channel($site->code . 'ldtfetch')->info('Retrying...');
                                            return $exception instanceof ConnectionException;
                                        })
                                        ->get($url)
                                        ->json();

                                    if (isset($result['data']) && !empty($result['data'])) {
                                        Log::channel($site->code . 'ldtfetch')->info('foreach count: ' . count($result['data']));

                                        foreach ($result['data'] as $enquiry) {
                                        
                                            $checkParticipantAlreadyExitQuery = ExternalEnquiry::where('channel_record_id', $enquiry['id'])
                                            ->where('site_id', $site->id)
                                            ->where('event_id', $event->id)
                                            ->where('event_category_event_third_party_id', $category->pivot->id)->first();

                                            if($checkParticipantAlreadyExitQuery) {
                                                Log::channel($site->code . 'ldtfetch')->info(
                                                    'channel_record_id: ' . $enquiry['id'] . ', event_id: ' . $event->id . ', event_category_event_third_party_id: ' . $category->pivot->id
                                                );
                                                continue;

                                            }
                                            $gender = match ($_gender = Str::lower($this->getColumnValue($enquiry['fields'], 'Gender'))) {
                                                Str::contains($_gender, ' female'), => GenderEnum::Female,
                                                Str::contains($_gender, ' male'), => GenderEnum::Male,
                                                default => GenderEnum::Other,
                                            };

                                            $gender = Str::contains(Str::lower($this->getColumnValue($enquiry['fields'], 'Gender')), 'female') ? GenderEnum::Female : GenderEnum::Male;
                                            $_dob = $this->getColumnValue($enquiry['fields'], 'Date of Birth');
                                            $canParse = strtotime($_dob);
                                            $dob = $canParse ? Carbon::parse($_dob)?->toDateString() : null;

                                            $now = Carbon::now();

                                            $estimatedFinishTime = $this->getEstimatedFinishTime($this->getColumnValue($enquiry['fields'], 'Estimated Finish Time (hh:mm:ss)'));

                                            $firstName = $this->getColumnValue($enquiry['fields'], 'First Name');
                                            $lastName = $this->getColumnValue($enquiry['fields'], 'Last Name');
                                            $email = $this->getColumnValue($enquiry['fields'], 'Email Address');

                                            $externalEnquiry = [
                                                'ref' => Str::orderedUuid()->toString(),
                                                'site_id' => $site->id,
                                                'channel_record_id' => $enquiry['id'],
                                                'event_id' => $event->id,
                                                // 'partner_channel_id' => $event->eventThirdParties[0]->partner_channel_id,
                                                'partner_channel_id' => $this->getPartnerChannel($category->pivot->eventThirdParty, $enquiry['incrementalStatus'], $site), // RunThrough Requirement
                                                'event_category_event_third_party_id' => $category->pivot->id,
                                                'charity_id' => $this->getCharity($this->getColumnValue($enquiry['fields'], 'Run for Charity?'))?->id,
                                                'run_for_charity' => $this->getColumnValue($enquiry['fields'], 'Run for Charity?') ?? null,
                                                'first_name' => $firstName ? ucwords(trim($firstName)) : null,
                                                'last_name' => $lastName ? ucwords(trim($lastName)) : null,
                                                'email' => $email ? trim($email) : null,
                                                'address' => $this->getColumnValue($enquiry['fields'], 'Address (Line 1)'),
                                                'city' => $this->getColumnValue($enquiry['fields'], 'Address (City)'),
                                                'postcode' => $this->getColumnValue($enquiry['fields'], 'Address (Postcode)'),
                                                'country' => $this->getColumnValue($enquiry['fields'], 'Address (Country)'),
                                                'phone' => $this->getColumnValue($enquiry['fields'], 'Phone Number'),
                                                'gender' => $gender,
                                                'dob' => $dob,
                                                'origin' => $enquiry["tracking"]["utmParams"]["utmSource"] ?? null,
                                                'emergency_contact_name' => $this->getColumnValue($enquiry['fields'], 'Emergency Contact'),
                                                'emergency_contact_phone' => $this->getColumnValue($enquiry['fields'], 'Emergency Phone'),
                                                'emergency_contact_relationship' => $this->getColumnValue($enquiry['fields'], 'Emergency contact relationship'),
                                                'created_at' => $now,
                                                'updated_at' => $now,
                                                'ldt_created_at' => Carbon::parse($enquiry['createdAt']),
                                                'ldt_updated_at' => Carbon::parse($enquiry['updatedAt']),
                                            ];

                                            $externalEnquiries = [...$externalEnquiries, $externalEnquiry];

                                            $weeklyPhysicalActivity = $this->getColumnValue($enquiry['fields'], 'How much physical activity do you participate in per week?');
                                            $ethnicity = $this->getEthnicity($this->getColumnValue($enquiry['fields'], 'Ethnicity'));

                                            $extraData[] = [ // Set the enquiry extra data
                                                'ref' => $externalEnquiry['ref'],
                                                'estimated_finish_time' => $estimatedFinishTime,
                                                'distance_like_to_run_here' => $this->getColumnValue($enquiry['fields'], 'Please enter the distance you would like to run here')  ?? null,
                                                'race_pack_posted' => $this->getColumnValue($enquiry['fields'], 'Would you like your race pack posted to you?') == BoolYesNoEnum::Yes->name ? BoolYesNoEnum::Yes->value : BoolYesNoEnum::No->value,
                                                'club' => $this->getColumnValue($enquiry['fields'], 'Club Name'),
                                                'ethnicity' => $ethnicity,
                                                'raced_before' => $this->getColumnValue($enquiry['fields'], 'Is this your first ever race?') == BoolYesNoEnum::Yes->name ? BoolYesNoEnum::Yes->value : BoolYesNoEnum::No->value,
                                                'speak_with_coach' => $this->getColumnValue($enquiry['fields'], 'Would you be interested in speaking with a personal running coach?') == BoolYesNoEnum::Yes->name ? BoolYesNoEnum::Yes->value : BoolYesNoEnum::No->value,
                                                'weekly_physical_activity' => isset($weeklyPhysicalActivity) ? ParticipantProfileWeeklyPhysicalActivityEnum::tryFrom(trim($weeklyPhysicalActivity)) : null,
                                                'reason_for_participating' => $this->getColumnValue($enquiry['fields'], 'Do you have a special or inspirational reason for taking part? If yes, please tell us your story.'),
                                                'hear_from_partner_charity' => $this->getColumnValue($enquiry['fields'], "Would you be interested in hearing from the event's charity partner Alzheimer's Research. If so tick here, by doing so you are giving Alzheimer's Research permission to contact you by phone or email about raising money for them in this or another event.") == BoolYesNoEnum::Yes->name ? BoolYesNoEnum::Yes->value : BoolYesNoEnum::No->value,
                                            ];
                                        }

                                        $participantCount += count($result['data']);
                                    }

                                    $queryParams = [
                                        ...$queryParams,
                                        'page' => [
                                            ...$queryParams['page'],
                                            'after' => $result['page']['next']
                                        ]
                                    ];

                                    Log::channel($site->code . 'ldtfetch')->info('Participants Count: '. json_encode($participantCount));

                                    if (count($externalEnquiries)) { // Insert and offer places
                                        ExternalEnquiry::insert($externalEnquiries);

                                        $externalEnquiryRefs = collect($externalEnquiries)->pluck('ref')->all();

                                        $this->dispatch(new OfferPlacesToLDTParticipantsJob($event, $category, $externalEnquiryRefs, $site, $extraData)); // Offer places to external enquiries
                                    }
                                } while ($result['page']['next']);

                                $data = [...$data, [
                                        'event' => $event,
                                        'participants_count' => $participantCount
                                    ]
                                ];
                            }
                        }
                    }
                });

            // $this->notifyAdmins($data, $site); // Notify site admins about the new enquiries
        } catch (Exception $exception) {
            if ($site) {
                Log::channel($site->code . 'ldtfetch')->error($exception);
            } else {
                Log::channel('ldtfetch')->error($exception);
            }
            $this->error($exception->getMessage());
            return Command::FAILURE;
        }

        Log::channel($site->code . 'ldtfetch')->info('Fetch participants from LDT END');

        return Command::SUCCESS;
    }

    /**
     * @param  array    $fields
     * @param  string   column
     * @return ?string
     * 
     */
    private function getColumnValue(array $fields, string $column): ?string
    {
        $value = null;

        foreach ($fields as $field) {
            if ($field['name'] == $column) {
                $value = $field['value'];
                break;
            }
        }

        if ($value) {
            if (is_string($value)) {
                return $value;
            }

            // if (is_array($value)) {

            // }
        }

        if (is_string($value) && empty($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param  EventThirdParty  $eventThirdParty
     * @param  string           $incrementalStatus
     * @param  Site             $site
     * @return ?string
     * 
     */
    private function getPartnerChannel(EventThirdParty $eventThirdParty, string $incrementalStatus, Site $site): ?string
    {
        switch ($incrementalStatus) {
            case "INCREMENTAL":
                $value = PartnerChannel::where('code', PredefinedPartnerChannelEnum::Incremental->value)
                    ->whereHas('partner', function ($query) use ($site)  {
                        $query->whereHas('site', function ($query) use ($site) {
                            $query->where('id', $site->id);
                        });
                    })->value('id');
                break;
            case "NON_INCREMENTAL":
                $value = PartnerChannel::where('code', PredefinedPartnerChannelEnum::NonIncremental->value)
                    ->whereHas('partner', function ($query) use ($site)  {
                        $query->whereHas('site', function ($query) use ($site) {
                            $query->where('id', $site->id);
                        });
                    })->value('id');
                break;
            case "UNRECOGNIZED":
                $value = PartnerChannel::where('code', PredefinedPartnerChannelEnum::Unrecognized->value)
                    ->whereHas('partner', function ($query) use ($site)  {
                        $query->whereHas('site', function ($query) use ($site) {
                            $query->where('id', $site->id);
                        });
                    })->value('id');
                break;
            case "PENDING":
                $value = PartnerChannel::where('code', PredefinedPartnerChannelEnum::Pending->value)
                    ->whereHas('partner', function ($query) use ($site)  {
                        $query->whereHas('site', function ($query) use ($site) {
                            $query->where('id', $site->id);
                        });
                    })->value('id');
                break;
            default:
                if ($partnerId = Partner::where('slug', PredefinedPartnersEnum::LetsDoThis->value)
                        ->where('site_id', $site->id)
                        ->value('id')
                    ) {
                    $value = PartnerChannel::firstOrCreate([
                            'code' => PredefinedPartnersEnum::LetsDoThis->value . '-' . Str::slug($incrementalStatus),
                            'partner_id' => $partnerId
                        ],
                        [
                            'name' => Str::replace('_', ' ', Str::ucfirst(Str::lower($incrementalStatus))),
                        ])->id;
                    Log::channel($site->code . 'ldtfetch')->info('New LDT Channel Created.');
                    Log::channel($site->code . 'ldtfetch')->info('Channel: '. $incrementalStatus);
                } else {
                    $value = null;
                    Log::channel($site->code . 'ldtfetch')->error('The ' . PredefinedPartnersEnum::LetsDoThis->name . ' partner does not exists!');
                    Log::channel($site->code . 'ldtfetch')->info('Unable to create New LDT Channel.');
                    Log::channel($site->code . 'ldtfetch')->info('Channel: '. $incrementalStatus);
                }
                break;
        }

        return $value;
    }

    /**
     * @param  string|null  $incrementalStatus
     * @return string|null
     * 
     */
    private function getEthnicity(?string $ethnicity = null): ?string
    {
        $ethnicity = isset($ethnicity) ? trim($ethnicity) : null;

        switch ($ethnicity) {
            case "White British":
                $value = ProfileEthnicityEnum::WhiteBritish->value;
                break;
            case "White Other":
                $value = ProfileEthnicityEnum::WhiteOther->value;
                break;
            case "Asian or Asian British":
                $value = ProfileEthnicityEnum::AsianOrAsianBritish->value;
                break;
            case "Prefer not to say":
                $value = ProfileEthnicityEnum::PreferNotToSay->value;
                break;
            case "Mixed or Multiple Ethnic Groups":
                $value = ProfileEthnicityEnum::MixedOrMultipleEthnicGroups->value;
                break;
            case "Black or Black British":
                $value = ProfileEthnicityEnum::BlackOrBlackBritish->value;
                break;
            case "Other Ethnic Group":
                $value = ProfileEthnicityEnum::OtherEthnicGroup->value;
                break;
            default:
                $value = null;
                break;
        }

        return $value;
    }

    /**
     * Get the LDT corresponding charity.
     * 
     * @param  string        $name
     * @return Charity|null
     */
    private function getCharity(?string $name = null): Charity|null
    {
        if ($name) {
            $name = trim(\Str::contains($name, '-') ? substr($name, 0, strripos($name, '-')) : $name);
            $charity = Charity::where('name', 'like', $name . '%')->first();
        }

        return $charity ?? null;
    }

    /**
     * Get the estimated finish time. Convert it from string to it's corresponding time format
     * 
     * @param  string|null  $time
     * @return string|null
     */
    private function getEstimatedFinishTime(?string $time = null): string|null
    {
        $_time = $time;

        $_time = Str::replace('.', ':', $_time);
        $_time = Str::replace(';', ':', $_time);
        $_time = Str::replace('hr', 'hour', $_time);

        $_time = strlen($_time) == 2 ? (int) $_time : trim($_time);

        if (strlen($_time) == 5) { // For cases like 47:00. Convert them to 00:47:00 instead of null (since 47 > 24h)
            $_time = (int) substr($_time, 0, 2) > 23 ? '00:'.$_time : $_time;
        }

        $canParse = strtotime(is_int($_time) ? $_time. ' mins' : $_time, 0);

        return $canParse
            ? Carbon::parse($canParse)?->toTimeString()
            : $time;
    }

    /**
     * Notify site admins about the new enquiries
     * 
     * @param  array $data
     * @param  Site  $site
     * @return void
     */
    private function notifyAdmins(array $data, Site $site): void
    {
        if (count($data) > 0) {
            $siteAdmins = User::whereHas('roles', function ($query) { // Get Site Admins
                $query->where('name', RoleNameEnum::Administrator);
            })->whereHas('sites', function ($query) use ($site) {
                $query->where('domain', $site->domain);
            })->get();

            $cc = $siteAdmins->pluck('email')->all();
            unset($cc[0]);

            try {
                Mail::site()->to($siteAdmins[0]->email)->cc($cc)->queue(new NewLDTRegistrations('New Registrations from Let\'s Do This', $data)); // Dispatch a job that notifies the Site admin about the new enquiries
            } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("Fetch Participant from LDT - Notify Admins");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob(new NewLDTRegistrations('New Registrations from Let\'s Do This', $data), clientSite()));
            } catch (\Exception $e) {
                Log::channel(static::getSite()?->code . 'mailexception')->info("Fetch Participant from LDT - Notify Admins");
                Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                dispatch(new ResendEmailJob(new NewLDTRegistrations('New Registrations from Let\'s Do This', $data), clientSite()));
            }
        }
    }
}
