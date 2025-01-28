<?php

namespace App\Jobs;

use DB;
use Log;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\QueryException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Helpers\EmailAddressHelper;
use App\Http\Helpers\ExternalEnquiryHelper;

use App\Enums\QueueNameEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Enums\ParticipantPaymentStatusEnum;

use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Participant\Models\Participant;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Event\Models\EventEventCategory;

use App\Events\ParticipantNewRegistrationsEvent;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EventDataService;
use App\Services\DataServices\EnquiryDataService;
use App\Services\DataServices\ParticipantDataService;

class OfferPlacesToLDTParticipantsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $event;
    public $eventCategory;
    public $externalEnquiryRefs;
    public $extraData;
    public $site;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * Create a new job instance.
     * 
     * @param  Event  $event
     * @param  array  $externalEnquiryRefs
     * @param  Site   $site
     * @param  array  $extraData
     * @return void
     */
    public function __construct(Event $event, EventCategory $eventCategory, array $externalEnquiryRefs, Site $site, array $extraData)
    {
        $this->onConnection(QueueNameEnum::LdtOffer->value); // Set the connection name

        $this->event = $event;
        $this->eventCategory = $eventCategory;
        $this->externalEnquiryRefs = $externalEnquiryRefs;
        $this->site = $site;
        $this->extraData = $extraData;
        $this->queue = QueueNameEnum::LdtOffer->value;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pid = getmypid();

        try {
            Cache::put('command-site-' . $pid,  $this->site, now()->addHour());
            Log::channel($this->site->code . 'ldtofferprocess')->info('Offer Process ID: ' . $pid);

            $eec = EventEventCategory::with(['event', 'eventCategory'])
                ->where('event_id', $this->event->id)
                ->where('event_category_id', $this->eventCategory->id)
                ->firstOrFail();

            $enquiries = ExternalEnquiry::with(['event', 'eventCategoryEventThirdParty'])
                ->whereIn('ref', $this->externalEnquiryRefs)
                ->filterByAccess()
                ->get();

            foreach ($enquiries as $enquiry) {
                if ($enquiry->email) {
                    try {
                        if ($enquiry->participant_id) { // TODO: Log a message to a channel for developers. Message: "The enquiry has already been offered a place and converted to a participant! This log is probably caused by a failed job that is retrying."
                            throw new \Exception('The enquiry has already been offered a place and converted to a participant!');
                            // TODO: Log a message to a channel for developers
                        }

                        if (! EmailAddressHelper::isValid($enquiry->email)) { // Validate the email address to ensure the accuracy of our data
                            throw new \Exception("The email address {$enquiry->email} is not valid!");
                        }

                        try { // CHECK IF THE PARTICIPANT CAN REGISTER AND REGISTER THE PARTICIPANT
                            DB::beginTransaction();

                            $register = $this->register($enquiry, $eec, $this->getExtraData($enquiry));

                            DB::commit();

                            $passed = [
                                'eecs' => [
                                    [
                                        'id' => $eec->id,
                                        'ref' => $eec->ref,
                                        'name' => $eec->event->formattedName,
                                        'category' => $eec->eventCategory?->name,
                                        'reg_status' => $register->result->status,
                                        'registration_fee' => $eec->userRegistrationFee($register->result->user),
                                        'participant' => $register->result->participant
                                    ]
                                ]
                            ];

                            $extraData = [
                                'passed' => $passed,
                                'wasRecentlyCreated' => $register->result->wasRecentlyCreated
                            ];

                            try {
                                // Ensure end_date exists and check if it is not in the past
                                
                                if ($enquiry->ldt_created_at && Carbon::parse($enquiry->ldt_created_at)->toDateString() >= Carbon::now()->toDateString()){
                                    // Notify participant via email
                                    event(new ParticipantNewRegistrationsEvent(
                                        $register->result->user, 
                                        $extraData, 
                                        $register->result->participant->invoiceItem?->invoice, 
                                        $this->site, 
                                        $enquiry, 
                                        $register->participant_extra
                                    ));
                            
                                    // Log success
                                    Log::channel($this->site->code . 'ldtoffer')
                                        ->debug('Place offered and email sent!');
                                } else {
                                    // Optional log if end_date has passed or is missing
                                    Log::channel($this->site->code . 'ldtoffer')
                                        ->info('No email sent. Event end_date is past or missing.');
                                }
                            } catch (Exception $e) {
                                // Log exception and reason
                                Log::channel($this->site->code . 'ldtoffer')
                                    ->info("Participant Registration Exception. Unable to process LDT offer mail. " . $e->getMessage());
                                
                                Log::channel($this->site->code . 'ldtoffer')->info($e);
                            
                                // Update the enquiry record with error message
                                $message = "Exception " . Carbon::now()->toDateString() . ": Participant Registration Exception. Unable to process LDT offer mail.";
                                $this->updateEquiryAfterExceptionOccurred($enquiry, $message);
                            }
                            
                        } catch (QueryException $e) {
                            DB::rollback();

                            Log::channel($this->site->code . 'ldtoffer')->info('Unable to create the participant! Please try again.' . json_encode($e->getMessage()));
                            Log::channel($this->site->code . 'ldtoffer')->info('Unable to create the participant! Please try again.' . json_encode($e));
                            Log::channel($this->site->code . 'ldtoffer')->info('QueryException_Event: ' . json_encode(collect($this->event->toArray())->only(['id', 'name'])->all()));
                            Log::channel($this->site->code . 'ldtoffer')->info('QueryException_Event_Category: ' . json_encode(collect($this->eventCategory->toArray())->only(['id', 'name', 'pivot'])->all()));
                            Log::channel($this->site->code . 'ldtoffer')->info('QueryException_Enquiry: ' . json_encode(collect($enquiry->toArray())->only(['id', 'email', 'first_name', 'last_name'])->all()));
                            $message = "Exception " . Carbon::now()->toDateString() . ": " . $e->getMessage();
                            $this->updateEquiryAfterExceptionOccurred($enquiry, $message);
                            continue;
                        } catch (Exception $e) {
                            DB::rollback();

                            Log::channel($this->site->code . 'ldtoffer')->info($e->getMessage());
                            Log::channel($this->site->code . 'ldtoffer')->info($e);
                            Log::channel($this->site->code . 'ldtoffer')->info('Exception_Event: ' . json_encode(collect($this->event->toArray())->only(['id', 'name'])->all()));
                            Log::channel($this->site->code . 'ldtoffer')->info('Exception_Event_Category: ' . json_encode(collect($this->eventCategory->toArray())->only(['id', 'name', 'pivot'])->all()));
                            Log::channel($this->site->code . 'ldtoffer')->info('Exception_Enquiry: ' . json_encode(collect($enquiry->toArray())->only(['id', 'email', 'first_name', 'last_name'])->all()));
                            $message = "Exception " . Carbon::now()->toDateString() . ": " . $e->getMessage();
                            $this->updateEquiryAfterExceptionOccurred($enquiry, $message);
                            continue;
                        }
                    } catch (Exception $e) {
                        Log::channel($this->site->code . 'ldtoffer')->info($e->getMessage());
                        Log::channel($this->site->code . 'ldtoffer')->info('Exception_Event: ' . json_encode(collect($this->event->toArray())->only(['id', 'name'])->all()));
                        Log::channel($this->site->code . 'ldtoffer')->info('Exception_Event_Category: ' . json_encode(collect($this->eventCategory->toArray())->only(['id', 'name', 'pivot'])->all()));
                        Log::channel($this->site->code . 'ldtoffer')->info('Exception_Enquiry: ' . json_encode(collect($enquiry->toArray())->only(['id', 'email', 'first_name', 'last_name'])->all()));
                        $message = "Exception " . Carbon::now()->toDateString() . ": " . $e->getMessage();
                        $this->updateEquiryAfterExceptionOccurred($enquiry, $message);
                        continue;
                    }
                }
            }

            CacheDataManager::flushAllCachedServiceListings(new EnquiryDataService()); // Clear the enquiry data cache
            CacheDataManager::flushAllCachedServiceListings(new EventDataService()); // Clear the event data cache
            CacheDataManager::flushAllCachedServiceListings(new ParticipantDataService()); // Clear the participant data cache

            Cache::forget('command-site-' . $pid);
        } catch (ModelNotFoundException $e) {
            Cache::forget('command-site-' . $pid);

            Log::channel($this->site->code . 'ldtoffer')->info('The event category does not belongs to the event! ' . json_encode($e->getMessage()));
            Log::channel($this->site->code . 'ldtoffer')->info('ModelNotFoundException_Event: ' . json_encode(collect($this->event->toArray())->only(['id', 'name'])->all()));
            Log::channel($this->site->code . 'ldtoffer')->info('ModelNotFoundException_Event_Category: ' . json_encode(collect($this->eventCategory->toArray())->only(['id', 'name', 'pivot'])->all()));
        }
    }

    /**
     * @param  ExternalEnquiry     $enquiry
     * @param  EventEventCategory  $eec
     * @param  array               $extraData
     * @return object
     */
    private function register(ExternalEnquiry $enquiry, EventEventCategory $eec, array $extraData): object
    {
        $request = new Request();
        $request['email'] = $enquiry->email;
        $request['first_name'] = $enquiry->first_name;
        $request['last_name'] = $enquiry->last_name;
        $request['phone'] = $enquiry->phone;
        $request['profile'] = [
            'dob' => $enquiry->dob,
            'city' => $enquiry->city,
            'region' => $enquiry->region,
            'country' => $enquiry->country,
            'address' => $enquiry->address,
            'gender' => $enquiry->gender,
            'postcode' => $enquiry->postcode,
            'ethnicity' => $extraData['ethnicity'] ?? null,
           'participant_profile' => [
                'emergency_contact_name' => $enquiry->emergency_contact_name,
                'emergency_contact_phone' => $enquiry->emergency_contact_phone,
                'club' => $extraData['club'] ?? null,
                'distance_like_to_run_here' => $extraData['distance_like_to_run_here'] ?? null,
                'race_pack_posted' => $extraData['race_pack_posted'] ?? null,
                'weekly_physical_activity' => $extraData['weekly_physical_activity'] ?? null
            ]
        ];
        $request['payment_status'] = ParticipantPaymentStatusEnum::Waived->value;
        $request['waive'] = ParticipantWaiveEnum::Completely->value;
        $request['waiver'] = ParticipantWaiverEnum::Partner->value;
        $request['ldt_created_at'] = $enquiry->ldt_created_at;
        $request['site_id'] = $enquiry->site_id;

        if (! empty($extraData)) {
            $request['participant'] = [
                'raced_before' => $extraData['raced_before'],
                'estimated_finish_time' => $extraData['estimated_finish_time'],
                'speak_with_coach' => $extraData['speak_with_coach'],
                'hear_from_partner_charity' => $extraData['hear_from_partner_charity'],
                'reason_for_participating' => $extraData['reason_for_participating'],
            ];
        }

        $register = Participant::registerForEvent($request, $eec, ParticipantAddedViaEnum::ExternalEnquiryOffer, null, true);

        if ($register->isDoubleRegistration) { // Create participantExtra when a double registration was made with some differences on profile information 
            if (ExternalEnquiryHelper::isProfileDifferentFromParentRecordProfile($register->participant, $enquiry)) {
                $participantExtra = ExternalEnquiryHelper::createParticipantExtraProfile($register->participant, $enquiry, $extraData);
                Log::channel($this->site->code . 'ldtoffer')->info('Participant_Extra Created: ' . json_encode(collect($participantExtra->toArray())->all()));
            }
        }

        // Update the external enquiry
        $enquiry->participant_id = $register->participant->id;
        $enquiry->converted = true;
        $timeline = $enquiry->timeline;
        $timeline[] = ['caption' => 'Converted', 'value' => $enquiry->converted, 'datetime' => $enquiry->updated_at];
        $enquiry->timeline = $timeline;
        $enquiry->save();

        // Update the website enquiry
        Enquiry::where('email', $enquiry->email)
            ->where('event_id', $this->event->id)
            ->where('event_category_id', $this->eventCategory->id)
            ->update([
                'participant_id' => $register->participant->id,
                'external_enquiry_id' => $enquiry->id
            ]);

        return (object) [
            'request' => $request,
            'result' => $register,
            'participant_extra' => $participantExtra ?? null
        ];
    }

    /**
     * @param  ExternalEnquiry  $enquiry
     * @return array
     */
    private function getExtraData(ExternalEnquiry $enquiry): array
    {
        return collect($this->extraData)->firstWhere('ref', $enquiry->ref) ?? [];
    }

    /**
     * @param  ExternalEnquiry  $enquiry
     * @param  string           $message
     * @return ExternalEnquiry
     */
    private function updateEquiryAfterExceptionOccurred(ExternalEnquiry $enquiry, string $message): ExternalEnquiry
    {
        $enquiry->comments = $enquiry->comments . "\n" . $message;
        $enquiry->save();

        return $enquiry;
    }
}
