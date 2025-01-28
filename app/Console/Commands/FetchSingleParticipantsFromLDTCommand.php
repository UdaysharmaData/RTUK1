<?php
namespace App\Console\Commands;
use Str;
use Exception;
use App\Mail\Mail;
use Carbon\Carbon;
use App\Enums\GenderEnum;
use App\Enums\RoleNameEnum;
use App\Enums\BoolYesNoEnum;
use App\Jobs\ResendEmailJob;
use Illuminate\Console\Command;
use App\Mail\NewLDTRegistrations;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use App\Enums\ProfileEthnicityEnum;
use App\Modules\Event\Models\Event;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\Http;
use App\Enums\PredefinedPartnersEnum;
use App\Modules\Charity\Models\Charity;
use App\Modules\Partner\Models\Partner;
use App\Enums\PredefinedPartnerChannelEnum;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventThirdParty;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Modules\Partner\Models\PartnerChannel;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use Illuminate\Http\Client\ConnectionException;
use App\Jobs\OfferPlacesToLDTSingleParticipantsJob;
use App\Modules\Event\Models\EventCategoryEventThirdParty;
use App\Enums\ParticipantProfileWeeklyPhysicalActivityEnum;

class FetchSingleParticipantsFromLDTCommand extends Command
{
    use DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'external-enquiry:fetch-from-ldt-single {site}';
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
            $headers = ['Content-Type' => 'application/json'];
            $externalEnquiries = [];
            $extraData = [];
            $limit = config('app.external_enquiries_limit', 100);

            // Fetch all external enquiries with site and eventThirdParties loaded to reduce DB calls
            $externalEnquiriesData = ExternalEnquiry::whereNotNull('comments')
                ->whereNull('participant_id')
                ->with(['site', 'event.eventThirdParties.eventCategories'])
                ->where('site_id', $site->id)
                ->where('created_at', '<', Carbon::today())
                ->where('processed', 0)
                ->take($limit)
                ->get();

            foreach ($externalEnquiriesData as $externalEnquiryData) {
                $site = $externalEnquiryData->site;
                $event = $externalEnquiryData->event;

                if (!$event || $event->eventThirdParties->isEmpty()) {
                    Log::channel($site->code . 'ldtfetch')->error('Event not found or has no eventThirdParties for ID: ' . $externalEnquiryData->id);
                    continue;
                }

                $event_category_event_third_party_id = $externalEnquiryData->event_category_event_third_party_id;

                if (empty($event_category_event_third_party_id)) {
                    throw new Exception("event_category_event_third_party_id is not defined in externalEnquiryData.");
                }

                $category = $event->eventThirdParties
                    ->flatMap(fn($eventThirdParty) => $eventThirdParty->eventCategories)
                    ->first(fn($category) => $category->pivot->id === $event_category_event_third_party_id);

                if (empty($category)) {
                    throw new Exception('Category not found');
                }
                
                $url = config('apiclient.ldt_single_participant_endpoint') . "$externalEnquiryData->channel_record_id";
            
                $result = Http::withHeaders($headers)
                    ->withToken(config('apiclient.ldt_token'))
                    ->accept('application/json')
                    ->retry(1, 100, function ($exception) use ($site) {
                        return $exception instanceof ConnectionException;
                    })
                    ->get($url)
                    ->json();

                if (!isset($result['id'])) {
                    Log::channel($site->code . 'ldtfetch')->warning('No participant data found for ' . $externalEnquiryData->channel_record_id);
                    continue;
                }

                // Check if participant already exists
                $existingParticipant = ExternalEnquiry::where('channel_record_id', $result['id'])
                    ->where('site_id', $site->id)
                    ->where('event_id', $result['eventId'])
                    ->where('event_category_event_third_party_id', $externalEnquiryData->event_category_event_third_party_id)
                    ->first();

                if ($existingParticipant) {
                    Log::channel($site->code . 'ldtfetch')->info('Participant already exists. ID: ' . $result['id']);
                    continue;
                }

                // Map participant details
                $externalEnquiry = $this->mapParticipantData($result, $event, $category, $site);
                $externalEnquiries[] = $externalEnquiry;

                // Map extra data
                $extraData[] = $this->mapExtraData($result, $externalEnquiry['ref']);
                
                $externalEnquiryRefs = collect($externalEnquiries)
                ->pluck('channel_record_id')
                ->unique()
                ->all();                    
            
                DB::table('external_enquiries')->where('id', $externalEnquiryData->id)->update(['processed' => 1]);
                $this->dispatch((new OfferPlacesToLDTSingleParticipantsJob($event, $category, $externalEnquiryRefs, $site, $extraData))->onQueue('ldtoffer'));
                DB::table('external_enquiries')->where('id', $externalEnquiryData->id)->update(['processed' => 2]);
                
            }
        } catch (Exception $exception) {
            Log::error($exception);
            $this->error($exception->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }


    /**
     * Maps the extra data for a participant from the LDT API to the local participant model.
     *
     * @param array $result The participant data from the LDT API.
     * @param string $ref The reference for the participant being mapped.
     *
     * @return array The mapped extra data.
     */
    private function mapExtraData($result, $ref)
    {
        return [
            'ref' => $ref,
            'estimated_finish_time' => $this->getEstimatedFinishTime($this->getColumnValue($result['fields'], 'Estimated Finish Time (hh:mm:ss)')),
            'distance_like_to_run_here' => $this->getColumnValue($result['fields'], 'Please enter the distance you would like to run here') ?? null,
            'race_pack_posted' => $this->getColumnValue($result['fields'], 'Would you like your race pack posted to you?') == BoolYesNoEnum::Yes->name ? BoolYesNoEnum::Yes->value : BoolYesNoEnum::No->value,
            'club' => $this->getColumnValue($result['fields'], 'Club Name'),
            'ethnicity' => $this->getEthnicity($this->getColumnValue($result['fields'], 'Ethnicity')),
            'raced_before' => $this->getColumnValue($result['fields'], 'Is this your first ever race?') == BoolYesNoEnum::Yes->name ? BoolYesNoEnum::Yes->value : BoolYesNoEnum::No->value,
            'speak_with_coach' => $this->getColumnValue($result['fields'], 'Would you be interested in speaking with a personal running coach?') == BoolYesNoEnum::Yes->name ? BoolYesNoEnum::Yes->value : BoolYesNoEnum::No->value,
            'weekly_physical_activity' => ParticipantProfileWeeklyPhysicalActivityEnum::tryFrom(trim($this->getColumnValue($result['fields'], 'Weekly Physical Activity'))),
            'reason_for_participating' => $this->getColumnValue($result['fields'], 'Do you have a special or inspirational reason for taking part?'),
            'hear_from_partner_charity' => $this->getColumnValue($result['fields'], "Would you be interested in hearing from the event's charity partner") == BoolYesNoEnum::Yes->name ? BoolYesNoEnum::Yes->value : BoolYesNoEnum::No->value,
        ];
    }


    /**
     * Map participant data to an array for database insertion.
     */
    private function mapParticipantData($result, $event, $category, $site)
    {
        $_gender = Str::lower($this->getColumnValue($result['fields'], 'Gender'));
        $gender = match (true) {
            Str::contains($_gender, 'female') => GenderEnum::Female,
            Str::contains($_gender, 'male') => GenderEnum::Male,
            default => GenderEnum::Other,
        };

        $_dob = $this->getColumnValue($result['fields'], 'Date of Birth');
        $dob = strtotime($_dob) ? Carbon::parse($_dob)->toDateString() : null;

        return [
            'ref' => Str::orderedUuid()->toString(),
            'site_id' => $site->id,
            'channel_record_id' => $result['id'],
            'event_id' => $event->id,
            'partner_channel_id' => $category->partner_channel_id,
            'event_category_event_third_party_id' => $category->pivot->id,
            'charity_id' => $this->getCharity($this->getColumnValue($result['fields'], 'Run for Charity?'))?->id,
            'run_for_charity' => $this->getColumnValue($result['fields'], 'Run for Charity?') ?? null,
            'first_name' => ucwords(trim($this->getColumnValue($result['fields'], 'First Name'))),
            'last_name' => ucwords(trim($this->getColumnValue($result['fields'], 'Last Name'))),
            'email' => trim($this->getColumnValue($result['fields'], 'Email Address')),
            'address' => $this->getColumnValue($result['fields'], 'Address (Line 1)'),
            'city' => $this->getColumnValue($result['fields'], 'Address (City)'),
            'postcode' => $this->getColumnValue($result['fields'], 'Address (Postcode)'),
            'country' => $this->getColumnValue($result['fields'], 'Address (Country)'),
            'phone' => $this->getColumnValue($result['fields'], 'Phone Number'),
            'gender' => $gender,
            'dob' => $dob,
            'origin' => $result['tracking']['utmParams']['utmSource'] ?? null,
            'emergency_contact_name' => $this->getColumnValue($result['fields'], 'Emergency Contact'),
            'emergency_contact_phone' => $this->getColumnValue($result['fields'], 'Emergency Phone'),
            'emergency_contact_relationship' => $this->getColumnValue($result['fields'], 'Emergency contact relationship'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'ldt_created_at' => Carbon::parse($result['createdAt']),
            'ldt_updated_at' => Carbon::parse($result['updatedAt']),
        ];
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
                    ->whereHas('partner', function ($query) use ($site) {
                        $query->whereHas('site', function ($query) use ($site) {
                            $query->where('id', $site->id);
                        });
                    })->value('id');
                break;
            case "NON_INCREMENTAL":
                $value = PartnerChannel::where('code', PredefinedPartnerChannelEnum::NonIncremental->value)
                    ->whereHas('partner', function ($query) use ($site) {
                        $query->whereHas('site', function ($query) use ($site) {
                            $query->where('id', $site->id);
                        });
                    })->value('id');
                break;
            case "UNRECOGNIZED":
                $value = PartnerChannel::where('code', PredefinedPartnerChannelEnum::Unrecognized->value)
                    ->whereHas('partner', function ($query) use ($site) {
                        $query->whereHas('site', function ($query) use ($site) {
                            $query->where('id', $site->id);
                        });
                    })->value('id');
                break;
            case "PENDING":
                $value = PartnerChannel::where('code', PredefinedPartnerChannelEnum::Pending->value)
                    ->whereHas('partner', function ($query) use ($site) {
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
                    $value = PartnerChannel::firstOrCreate(
                        [
                            'code' => PredefinedPartnersEnum::LetsDoThis->value . '-' . Str::slug($incrementalStatus),
                            'partner_id' => $partnerId
                        ],
                        [
                            'name' => Str::replace('_', ' ', Str::ucfirst(Str::lower($incrementalStatus))),
                        ]
                    )->id;
                    Log::channel($site->code . 'ldtfetch')->info('New LDT Channel Created.');
                    Log::channel($site->code . 'ldtfetch')->info('Channel: ' . $incrementalStatus);
                } else {
                    $value = null;
                    Log::channel($site->code . 'ldtfetch')->error('The ' . PredefinedPartnersEnum::LetsDoThis->name . ' partner does not exists!');
                    Log::channel($site->code . 'ldtfetch')->info('Unable to create New LDT Channel.');
                    Log::channel($site->code . 'ldtfetch')->info('Channel: ' . $incrementalStatus);
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
            $_time = (int) substr($_time, 0, 2) > 23 ? '00:' . $_time : $_time;
        }
        $canParse = strtotime(is_int($_time) ? $_time . ' mins' : $_time, 0);
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
