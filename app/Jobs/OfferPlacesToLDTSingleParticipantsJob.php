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
use App\Modules\User\Models\User;
class OfferPlacesToLDTSingleParticipantsJob implements ShouldQueue
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
            Log::channel($this->site->code . 'ldtofferprocess')->info('Offer Process ID: ' . $pid);

            $eec = EventEventCategory::with(['event', 'eventCategory'])
                ->where('event_id', $this->event->id)
                ->where('event_category_id', $this->eventCategory->id)
                ->firstOrFail();

            $enquiries = ExternalEnquiry::with(['event', 'eventCategoryEventThirdParty'])
                ->whereIn('channel_record_id', $this->externalEnquiryRefs)
                ->filterByAccess()
                ->get();

            foreach ($enquiries as $enquiry) {
                if ($enquiry->email) {
                    try {
                        if ($enquiry->participant_id) {
                            throw new \Exception('The enquiry has already been offered a place and converted to a participant!');
                        }

                        if (!EmailAddressHelper::isValid($enquiry->email)) {
                            throw new \Exception("The email address {$enquiry->email} is not valid!");
                        }

                        try {
                            DB::beginTransaction();
                            $register = $this->register($enquiry, $eec, $this->getExtraData($enquiry));
                            DB::commit();
                            $user = User::where('email', $enquiry->email)->firstOrFail();

                            $passed = [
                                'eecs' => [
                                    [
                                        'id' => $eec->id,
                                        'ref' => $eec->ref,
                                        'name' => $eec->event->formattedName,
                                        'category' => $eec->eventCategory?->name,
                                        'reg_status' => $register->result->status,
                                        'registration_fee' => $eec->userRegistrationFee($user),
                                        'participant' => $register->result->participant
                                    ]
                                ]
                            ];

                            $extraData = [
                                'passed' => $passed,
                                'wasRecentlyCreated' => $register->result->wasRecentlyCreated
                            ];
                          //  $this->updateEnquiryMessage($enquiry, "These records were modified because of a failure in the LDT Batch process.");

                        } catch (QueryException $e) {
                            DB::rollback();
                            Log::channel($this->site->code . 'ldtoffersingleparticipant')->info('Unable to create the participant (QueryException)! ' . $e->getMessage());
                            $this->updateEquiryAfterExceptionOccurred($enquiry, "QueryException: " . $e->getMessage());
                            continue;
                        } catch (Exception $e) {
                            DB::rollback();
                            Log::channel($this->site->code . 'ldtoffersingleparticipant')->info('Unable to create the participant (Exception)! ' . $e->getMessage());
                            $this->updateEquiryAfterExceptionOccurred($enquiry, "Exception: " . $e->getMessage());
                            continue;
                        }
                    } catch (Exception $e) {
                        Log::channel($this->site->code . 'ldtoffersingleparticipant')->info('Exception occurred: ' . $e->getMessage());
                        $this->updateEquiryAfterExceptionOccurred($enquiry, "Exception: " . $e->getMessage());
                        continue;
                    }
                }
            }

        } catch (ModelNotFoundException $e) {
            Log::channel($this->site->code . 'ldtoffersingleparticipant')->info('The event category does not belong to the event! ' . $e->getMessage());
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

        $register = Participant::registerForSingleEvent($request, $eec, ParticipantAddedViaEnum::ExternalEnquiryOffer, null, true);

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
