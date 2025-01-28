<?php

namespace App\Modules\Participant\Controllers;

use App\Enums\ActivityLogNameEnum;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Participant\Models\ParticipantExtra;
use DB;
use Log;
use Auth;
use Storage;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Facades\ClientOptions;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\AccountType;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;

use App\Http\Helpers\HeatOptions;
use App\Jobs\ParticipantsNotifyJob;

use App\Models\Invoice;
use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventPage;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventCustomField;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Participant\Models\ParticipantCustomField;

use App\Http\Requests\FamilyRegistrationRequest;
use App\Modules\Participant\Requests\ParticipantNotifyRequest;
use App\Modules\Participant\Requests\ParticipantUpdateRequest;
use App\Modules\Participant\Requests\ParticipantDeleteRequest;
use App\Modules\Participant\Requests\ParticipantOfferPlaceRequest;
use App\Modules\Participant\Requests\ParticipantListingQueryParamsRequest;

use App\Modules\Participant\Resources\ParticipantResource;

use App\Enums\GenderEnum;
use App\Enums\FeeTypeEnum;
use App\Enums\ListTypeEnum;
use App\Enums\BoolYesNoEnum;
use App\Enums\EventStateEnum;
use App\Enums\InvoiceStateEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ProfileEthnicityEnum;
use App\Enums\InvoiceItemStatusEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\ParticipantStateEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\BoolEnabledDisabledEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Enums\ParticipantActionTypeEnum;
use App\Enums\ParticipantPaymentStatusEnum;
use App\Enums\ListSoftDeletedItemsOptionsEnum;
use App\Enums\ParticipantProfileTshirtSizeEnum;
use App\Enums\ParticipantProfileWeeklyPhysicalActivityEnum;
use App\Events\ParticipantNewRegistrationsEvent;
use App\Modules\Event\Exceptions\EventEventCategoryException;
use App\Modules\Participant\Exceptions\IsRegisteredException;
use App\Modules\Participant\Exceptions\ParticipantTransferException;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;
use App\Traits\DownloadTrait;
use App\Traits\CustomFieldValueTrait;
use App\Traits\SingularOrPluralTrait;

use App\Modules\Participant\Requests\ParticipantRestoreRequest;

use App\Services\DefaultQueryParamService;
use App\Services\ClientOptions\Traits\Options;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EntryDataService;
use App\Services\DataServices\ParticipantDataService;
use App\Services\DataServices\PartnerEventDataService;
use App\Services\Reporting\Traits\ParticipantStatsTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Participants
 * Manages participants on the application
 * @authenticated
 */
class ParticipantController extends Controller
{
    use Response,
        SiteTrait,
        UploadTrait,
        CustomFieldValueTrait,
        DownloadTrait,
        SingularOrPluralTrait,
        Options,
        ParticipantStatsTrait;

    /*
    |--------------------------------------------------------------------------
    | Participant Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with participants. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected ParticipantDataService $participantService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_participants', [
            'except' => [],
        ]);
    }

    /**
     * The list of participants
     *
     * @queryParam state string Filter by state. Must be one of live, expired, archived. Example: live
     * @queryParam status string Filter by state. Must be one of paid, complete, notified, incomplete, clearance. Example: complete
     * @queryParam event string Filter by event slug. No-example
     * @queryParam charity string Filter by charity slug. No-example
     * @queryParam via string Filter by added via. Must be one of partner_events, book_events, registration_page, external_enquiry_offer, team_invitation, website. No-example
     * @queryParam gender string Filter by gender. Must be one of male, female. No-example
     * @queryParam tshirt_size string Filter by tshirt_size. Must be one of xxxs, xxs, xs, sm, m, l, xl, xxl, xxxl. No-example
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam payment_status string Filter by payment_status. Must be one of unpaid, paid, waived, refunded, transferred. Example: waived
     * @queryParam waive string Filter by waive. Must be one of partially, completely. Example: completely
     * @queryParam waiver string Filter by waiver. Must be one of partner, charity, corporate. Example: partner
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: first_name:desc,last_name:asc,full_name:desc
     * @queryParam year string Specifying year filter for when user was created. Example: 2023
     * @queryParam period string Specifying a period to filter users creation date by. Example: 24h
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param  ParticipantListingQueryParamsRequest  $request
     * @return JsonResponse
     */
    public function index(ParticipantListingQueryParamsRequest $request): JsonResponse
    {
        try {
            $participants = (new CacheDataManager(
                $this->participantService,
                'getPaginatedList',
                [$request],
            ))->getData();
        } catch (NotFoundExceptionInterface $e) {
            Log::error($e->getMessage());
            return $this->error('Unable to apply filter(s)', 400);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e->getMessage());
            return $this->error('An error occurred while fetching participants', 400);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->error('An error occurred while fetching participants', 400);
        }

        return $this->success('The list of participants', 200, [
            'participants' => $participants,
            'options' => ClientOptions::only('participants', [
                'genders',
                'statuses',
                'states',
                'deleted',
                'order_by',
                'order_direction',
                'years',
                'months',
                'time_periods',
                'payment_statuses',
                'waives',
                'waivers',
                'via'
            ]),
            'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Participants))->getDefaultQueryParams(),
            'action_messages' => Participant::$actionMessages
        ]);
    }

    /**
     * Edit a participant
     *
     * @urlParam participant_ref string required The ref of the participant. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     *
     * @param string $participant
     * @return JsonResponse
     * @throws Exception
     */
    public function edit(string $participant): JsonResponse
    {
        try {
            $_participant = (new CacheDataManager(
                $this->participantService,
                'edit',
                [$participant],
            ))->getData();

            try {
                $regActive = $_participant->eventEventCategory->registrationActive();

                if (!$regActive->status) { // Check if registrations are still active
                    throw new \Exception($regActive->message);
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
            }
        } catch (NotFoundExceptionInterface $e) {
            return $this->error('The participant was not found!', 404);
        }

        $pe = ParticipantExtra::select('club', 'distance_like_to_run_here', 'race_pack_posted')
            ->where('participant_id', $_participant->id)
            ->first();
        $ee = ExternalEnquiry::select('run_for_charity')
            ->where('participant_id', $_participant->id)
            ->first();
        $additionalFields = [
            'run_for_charity' => $ee ? $ee['run_for_charity'] ?: null : null,
            'club' => $pe ? $pe['club'] ?: null : null,
            'distance_like_to_run_here' => $pe ? $pe['distance_like_to_run_here'] ?: null : null,
            'race_pack_posted' => $pe ? $pe['race_pack_posted'] ?: null : null,
        ];

        return $this->success('Edit the participant', 200, [
            'event_status' => isset($message),
            'additional_six_feilds' => $additionalFields,
            'participant' => new ParticipantResource($_participant),
            'channels' => ParticipantAddedViaEnum::_options(),
            'states' => ParticipantStateEnum::_options(),
            'genders' => GenderEnum::_options(),
            'tshirt_sizes' => ParticipantProfileTshirtSizeEnum::_options(),
            'payment_statuses' => ParticipantPaymentStatusEnum::_options(),
            'speak_with_coach' => BoolYesNoEnum::_options(),
            'hear_from_partner_charity' => BoolYesNoEnum::_options(),
            'ethnicities' => ProfileEthnicityEnum::_options(),
            'weekly_physical_activities' => ParticipantProfileWeeklyPhysicalActivityEnum::_options(),
            'waives' => ParticipantWaiveEnum::_options(),
            'waivers' => ParticipantWaiverEnum::_options(),
            'fee_types' => FeeTypeEnum::_options(),
            'heat_options' => HeatOptions::generate(),
            'histories' => BoolYesNoEnum::_options(),
            'family_registrations' => BoolEnabledDisabledEnum::_options(),
            'action_messages' => Participant::$actionMessages,
            'offer_place_payment_statuses' => ParticipantPaymentStatusEnum::_options([ParticipantPaymentStatusEnum::Refunded, ParticipantPaymentStatusEnum::Transferred]),
        ]);
    }

    /**
     * Update a participant
     *
     * @urlParam participant_ref string required The ref of the participant. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     *
     * @param ParticipantUpdateRequest $request
     * @param string $participant
     * @return JsonResponse
     * @throws Exception
     */
    public function update(ParticipantUpdateRequest $request, string $participant): JsonResponse
    {
        $_participant = Participant::with(['invoiceItem.invoice.upload', 'participantCustomFields', 'participantExtra', 'user.profile.participantProfile'])
            ->where('ref', $participant)
            ->filterByAccess();

        $_participant = $_participant->whereHas('eventEventCategory.eventCategory', function ($query) {
            $query->whereHas('site', function ($q) {
                $q->hasAccess()
                    ->makingRequest();
            });
        });

        if (AccountType::isAdmin()) {
            $_participant = $_participant->withTrashed();
        }

        try {
            $_participant = $_participant->firstOrFail();

            try {
                DB::beginTransaction();

                $_participant->fill($request->only(['preferred_heat_time', 'raced_before', 'estimated_finish_time', 'enable_family_registration', 'speak_with_coach', 'hear_from_partner_charity', 'reason_for_participating']));

                if ($request->filled('user')) {
                    $_participant->user()->associate(User::where('ref', $request->user)->first());
                }

                if (AccountType::isAdminOrAccountManagerOrCharityOwnerOrCharityUserOrDeveloper()) { // Only the admin, account manager or charity (owner & user) can fill these fields
                    if ($request->filled('eec')) {
                        $_participant->eventEventCategory()->associate(EventEventCategory::where('ref', $request->eec)->first());
                    }

                    if ($request->filled('charity')) {
                        if (/*AccountType::isCharityOwnerOrCharityUser()*/AccountType::isCharityOwner()) { // TODO: Improve on the validation rules to ensure the authenticated charity can only select its charity. Throw a validation error otherwise. You may then get rid of this if else logic.
                            $_participant->charity_id = Auth::user()->charityUser?->charity_id; // TODO: handle the case charity_user role given that they can have more that one charities.
                        } else {
                            $_participant->charity()->associate(Charity::where('ref', $request->charity)->first());
                        }
                    }
                }

                if (AccountType::isAdmin()) { // Only the admin can fill these fields
                    if ($request->filled('event_page')) {
                        $_participant->eventPage()->associate(EventPage::where('ref', $request->event_page)->first());
                    }

                    $_participant = $_participant->fill($request->only(['state', 'added_via']));
                }

                $_participant->save();

                $profileData = [];
                $participantProfileData = [];

                if ($_participant->participantExtra) {
                    $_profileData = $request->only(['profile.dob',  'profile.gender', 'profile.ethnicity']);
                    $_profileData = $profileData['profile'] ?? [];
                    $_participant->participantExtra->update([...$request->only(['first_name', 'last_name', 'phone', 'weekly_physical_activity']), ...$_profileData]);
                } else {
                    // Update the user's info
                    $_participant->user()->update($request->only(['first_name', 'last_name', 'phone']));

                    // Set these properties here to ensure the database only gets hitted once.
                    $profileData = ['profile.dob', 'profile.gender', 'profile.ethnicity'];
                    $participantProfileData = ['weekly_physical_activity'];
                }

                $profileData = $request->only([...$profileData, 'profile.state', 'profile.address', 'profile.city', 'profile.country', 'profile.postcode', 'profile.nationality', 'profile.occupation', 'profile.passport_number']);
                $_participant->user->profile()->updateOrCreate([], $profileData['profile'] ?? []); // Update the user's profile (contains the event registration/required fields)

                $_participant->user->profile->participantProfile()->updateOrCreate([], $request->only([...$participantProfileData, 'slogan', 'club', 'emergency_contact_name', 'emergency_contact_phone', 'tshirt_size'])); // Update participant profile (contains the event registration/required fields)

                if ($request->filled('custom_fields')) { // Update the participant custom fields
                    $this->saveParticipantCustomFields($request, $_participant);
                }

                DB::commit();

                // Update payment status on different transaction
                DB::beginTransaction();

                if (AccountType::isAdminOrAccountManagerOrCharityOwnerOrCharityUserOrDeveloper()) { // Only the admin, account manager or charity (owner & user) can fill these fields
                    $this->updatePaymentStatus($request, $_participant);
                }

                DB::commit(); // Persist the data before validating the participant data required by the event

                $_participant->refresh(); // Refresh the participant

                $_participant->eventEventCategory['participant_registration_fee'] = $_participant->eventEventCategory->userRegistrationFee($_participant->user); // Update the registration fee to that for the user

                $_participant->canCompleteRegistration(); // Check if the participant can complete their registration. Throws exception otherwise

                // Validate event registration/required & custom fields and return a warning if the participant has not filled all the fields required for the event
                $result = Participant::updateParticipantStatus($_participant);

                CacheDataManager::flushAllCachedServiceListings(new EntryDataService());
                CacheDataManager::flushAllCachedServiceListings(new ParticipantDataService());
                (new CacheDataManager(new PartnerEventDataService(), 'participants'))->flushCachedServiceListings();

                if (!$result->status) {
                    return $this->success('Registration successfully updated! But there are warnings.', 200, [
                        'errors' => $result->errors,
                        'participant' => new ParticipantResource($_participant->load(['charity:id,ref,name,slug', 'eventEventCategory.event:id,ref,name,slug', 'eventEventCategory.event.eventCustomFields', 'eventEventCategory.eventCategory:id,ref,name,slug', 'participantCustomFields.eventCustomField', 'user.profile.participantProfile']))
                    ]);
                }
            } catch (QueryException $e) {
                DB::rollback();

                CacheDataManager::flushAllCachedServiceListings(new EntryDataService());
                CacheDataManager::flushAllCachedServiceListings($this->participantService);
                (new CacheDataManager(new PartnerEventDataService(), 'participants'))->flushCachedServiceListings();

                return $this->error('Unable to update the participant! Please try again.', 406, $e->getMessage());
            } catch (Exception $e) {
                DB::rollback();

                CacheDataManager::flushAllCachedServiceListings(new EntryDataService());
                CacheDataManager::flushAllCachedServiceListings($this->participantService);
                (new CacheDataManager(new PartnerEventDataService(), 'participants'))->flushCachedServiceListings();

                return $this->error($e->getMessage(), 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The participant was not found!', 404);
        }

        return $this->success('Successfully updated the participant!' . ($message ?? null) . '!', 200, new ParticipantResource($_participant->load(['charity:id,ref,name,slug', 'eventEventCategory.event:id,ref,name,slug', 'eventEventCategory.event.eventCustomFields', 'eventEventCategory.eventCategory:id,ref,name,slug', 'participantCustomFields.eventCustomField', 'user.profile.participantProfile'])));
    }

    /**
     * Check if the participant can be transferred to another event
     * 
     * @urlParam participant_ref string required The ref of the participant. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @bodyParam eec string required The event event category ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
     * @bodyParam custom_transfer_fee numeric required The custom transfer fee. Example: 5
     * @bodyParam cancel_difference boolean required Whether or not to cancel the difference. Example: true
     *
     * @param  Request      $request
     * @param  string       $participant
     * @return JsonResponse
     */
    public function verifyTransfer(Request $request, string $participant): JsonResponse
    {
        $validator = validator($request->all(), [
            'eec' => ['required', 'string', function ($attribute, $value, $fail) {
                $exists = EventEventCategory::where('ref', $value)
                    ->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->exists();
                if (!$exists) {
                    $fail('The selected event and category are invalid');
                }
            }],
            'custom_transfer_fee' => ['sometimes', 'required', 'numeric', 'min:0'],
            'cancel_difference' => ['sometimes', 'required', 'boolean']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $participant = Participant::where('ref', $participant)
                ->whereHas('eventEventCategory.eventCategory', function ($query) {
                    $query->whereHas('site', function ($q) {
                        $q->hasAccess()
                            ->makingRequest();
                    });
                })->firstOrFail();

            $eec = EventEventCategory::where('ref', $request->eec)->first();

            $result = Participant::validateTransfer($participant, $eec, $request->user());
        } catch (ModelNotFoundException $e) {
            return $this->error('The entry was not found!', 404);
        } catch (ParticipantTransferException $e) {
            return $this->error($e->getMessage(), 406, $e->errorData);
        } catch (EventEventCategoryException $e) {
            return $this->error($e->getMessage(), 406);
        } catch (IsRegisteredException $e) {
            return $this->error($e->getMessage(), 406);
        } catch (Exception $e) {
            Log::channel(static::getSite()?->code . 'adminanddeveloper')->info('Participant Transfer Exception.  - An error occurred while verifying the participant transfer! - ' . $e->getMessage());
            return $this->error('An error occurred while transferring the participant!', 406, $e->getMessage());
        }

        $message = $result['message'];
        unset($result['message']);

        return $this->success($message, 200, $result);
    }

    /**
     * Transfer a participant
     * 
     * @urlParam participant_ref string required The ref of the participant. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @bodyParam eec string required The event event category ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
     * @bodyParam custom_transfer_fee numeric required The custom transfer fee. Example: 5
     * @bodyParam cancel_difference boolean required Whether or not to cancel the difference. Example: true
     *
     * @param  Request      $request
     * @param  string       $participant
     * @return JsonResponse
     */
    public function transfer(Request $request, string $participant): JsonResponse
    {
        $validator = validator($request->all(), [
            'eec' => ['required', 'string', function ($attribute, $value, $fail) {
                $exists = EventEventCategory::where('ref', $value)
                    ->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->exists();
                if (!$exists) {
                    $fail('The selected category is invalid');
                }
            }],
            'custom_transfer_fee' => ['sometimes', 'required', 'numeric', 'min:0'],
            'cancel_difference' => ['sometimes', 'required', 'boolean']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $participant = Participant::with(['invoiceItem.invoice.upload', 'user'])
                ->where('ref', $participant)
                ->whereHas('eventEventCategory.eventCategory', function ($query) {
                    $query->whereHas('site', function ($q) {
                        $q->hasAccess()
                            ->makingRequest();
                    });
                })->firstOrFail();

            $eec = EventEventCategory::where('ref', $request->eec)->first();

            $result = Participant::transfer($participant, $eec, Auth::user());
            $message = $result['message'];
            unset($result['message']);

            return $this->success(
                $message,
                200,
                $result
            );
        } catch (ModelNotFoundException $e) {
            return $this->error('Participant not found!', 404);
        } catch (ParticipantTransferException $e) {
            return $this->error($e->getMessage(), 406, $e->errorData);
        } catch (EventEventCategoryException $e) {
            return $this->error($e->getMessage(), 406);
        } catch (IsRegisteredException $e) {
            return $this->error($e->getMessage(), 406);
        } catch (Exception $e) {
            Log::channel(static::getSite()?->code . 'adminanddeveloper')->info('Participant Transfer Exception.  - An error occurred while transferring the participant! - ' . $e->getMessage());
            return $this->error('An error occurred while transferring the participant!', 406, $e->getMessage());
        }
    }

    /**
     * Download a participant entry
     *
     * @urlParam participant_ref string required The participant ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
     *
     * @param  string                           $participant
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function download(string $participant): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        $participant = Participant::with(['invoiceItem.invoice.upload'])
            ->where('ref', $participant)
            ->filterByAccess();

        $participant = $participant->whereHas('eventEventCategory.eventCategory', function ($query) {
            $query->whereHas('site', function ($q) {
                $q->hasAccess()
                    ->makingRequest();
            });
        });

        if (AccountType::isAdmin()) {
            $participant = $participant->withTrashed();
        }

        try {
            $participant = $participant->firstOrFail();

            if ($participant->invoiceItem?->invoice?->upload?->url && Storage::disk(config('filesystems.default'))->exists($participant->invoiceItem->invoice->upload->url)) {
                $headers = [
                    'Content-Type' => 'text/pdf',
                ];

                $fileName = $participant->invoiceItem->invoice->name . '.pdf';
                $fileName = str_replace(array("/", "\\", ":", "*", "?", "«", "<", ">", "|"), "-", $fileName);

                $path = $participant->invoiceItem->invoice->upload->url;

                return static::_download($path, false, $fileName);
            } else {
                return $this->error('The pdf file was not found!', 406);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The participant was not found!', 406);
        }
    }

    /**
     * Delete one or many participants (Soft delete)
     *
     * @param  ParticipantDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(ParticipantDeleteRequest $request): JsonResponse
    {
        $participants = Participant::with(['user', 'eventEventCategory', 'invoiceItem.invoice'])
            ->filterByAccess()
            ->whereHas('eventEventCategory.eventCategory', function ($query) {
                $query->whereHas('site', function ($q) {
                    $q->hasAccess()
                        ->makingRequest();
                });
            })->whereIn('ref', $request->refs)
            ->get();

        $undeletedParticipants = [];

        try {
            foreach ($participants as $participant) {
                $deleteResult = Participant::customDelete($participant);
    
                if (!$deleteResult->status) {
                    $undeletedParticipants[] = "<br/>" . $participant->user->full_name . " - " . $deleteResult->message;
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('An error occurs while trying to delete participants', 406);
        }

        if (count($undeletedParticipants)) {
            return $this->error('The following participants could not be deleted:' . implode('', $undeletedParticipants), 406);
        } else {
            return $this->success('Successfully deleted the participant(s)', 200);
        }
    }

    /**
     * Restore one or many participants
     *
     * @param  ParticipantRestoreRequest $request
     * @return JsonResponse
     */
    public function restore(ParticipantRestoreRequest $request): JsonResponse
    {
        $participants = Participant::onlyTrashed()
            ->filterByAccess()
            ->whereHas('eventEventCategory.eventCategory', function ($query) {
                $query->whereHas('site', function ($q) {
                    $q->hasAccess()
                        ->makingRequest();
                });
            });

        try {
            $participants = $participants->whereIn('ref', $request->refs)->get();

            if (!$participants->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($participants as $participant) {
                    if ($participant->restore()) {
                        $participant->participantActions()->create([
                            'type' => ParticipantActionTypeEnum::Restored,
                            'user_id' => Auth::user()->id,
                            'role_id' => Auth::user()->activeRole?->role_id
                        ]);
                    }
                }

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error("Unable to restore the " . static::singularOrPlural(['participant', 'participants'], $request->refs) . "! Please try again.", 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error("The " . static::singularOrPlural(['participant was', 'participants were'], $request->refs) . " not found!", 404);
        }

        return $this->success('Successfully restored the participant(s)', 200);
    }

    /**
     * Delete one or many participants (Permanently)
     * Only the administrator can delete an participant permanently.
     *
     * @param  ParticipantDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(ParticipantDeleteRequest $request): JsonResponse
    {
        if (!AccountType::isAdmin()) { // Only the administrator can delete an event permanently.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $participants = Participant::whereHas('eventEventCategory.eventCategory', function ($query) { // The admin must have access to the site and must make the request from the site
            $query->whereHas('site', function ($q) {
                $q->hasAccess()
                    ->makingRequest();
            });
        });

        try {
            $participants = $participants->withTrashed()
                ->whereIn('ref', $request->refs)
                ->get();

            if (!$participants->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($participants as $participant) {
                    $participant->forceDelete();

                    // TODO: Refund the participant once the record gets deleted (Check previous implementation)
                }

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();
                return $this->error("Unable to delete the " . static::singularOrPlural(['participant', 'participants'], $request->refs) . " permanently! Please try again.", 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error("The " . static::singularOrPlural(['participant was', 'participants were'], $request->refs) . " was not found!", 404);
        }

        return $this->success("Successfully deleted the " . static::singularOrPlural(['participant', 'participants'], $request->refs) . " permanently", 200);
    }

    /**
     * Create a family member
     *
     * @urlParam participant_ref string required The participant ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
     *
     * @param  string       $participant
     * @return JsonResponse
     */
    public function createFamilyMember(string $participant): JsonResponse // TODO: Update family registrations logic
    {
        $_participant = Participant::filterByAccess();

        $_participant = $_participant->whereHas('eventEventCategory.eventCategory', function ($query) {
            $query->whereHas('site', function ($q) {
                $q->makingRequest();
            });
        });

        try {
            $_participant = $_participant->where('ref', $participant)
                ->firstOrFail();

            try {
                if (!$_participant->eventEventCategory->event) {
                    throw new ModelNotFoundException('The event was not found!');
                }

                try {
                    if (!$_participant->eventEventCategory->event->reg_family_registrations) {
                        throw new \Exception('The event does not allow family registrations!');
                    }

                    if (!$_participant->enable_family_registration) {
                        throw new \Exception('You have not enabled family registration! Update your profile and enable it to be able to add family members');
                    }

                    if ($_participant->charity) { // Check if the charity has available places for the given event & event category
                        $hasPlaces = Event::charityHasAvailablePlaces($_participant->eventEventCategory, $_participant->charity);
                    } else { // Check if the event has available places for the given event category
                        $hasPlaces = Event::hasAvailablePlaces($_participant->eventEventCategory);
                    }

                    if (!$hasPlaces->status) {
                        if (isset($charity) && $charity->latestCharityMembership?->type == CharityMembershipTypeEnum::Partner) { // Partner charities are only entitled to one registration for all events per year. // TODO: Revise this logic while working on family registrations
                            // event(new PartnerCharityAttemptedRegistrationEvent($charity, $_participant->eventEventCategory, collect($participant->user)->toArray()));
                        }

                        throw new \Exception($hasPlaces->message);
                    }
                } catch (Exception $e) {
                    return $this->error($e->getMessage(), 406);
                }
            } catch (ModelNotFoundException $e) {
                return $this->error($e->getMessage(), 406);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The participant was not found!', 404);
        }

        return $this->success('Add the family member!', 200, [
            'participant' => new ParticipantResource($_participant->load('familyRegistrations')),
            'genders' => GenderEnum::_options()
        ]);
    }

    /**
     * Store the family member
     *
     * @urlParam participant_ref string required The participant ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
     *
     * @param  FamilyRegistrationRequest  $request
     * @param  string                     $participant
     * @return JsonResponse
     */
    public function storeFamilyMember(FamilyRegistrationRequest $request, string $participant): JsonResponse
    {
        $_participant = Participant::whereHas('participantCustomFields', function ($query) {
            $query->where('key', 'cf_family_registrations')
                ->whereNotNull('value');
        });

        try {
            $_participant = $_participant->where('ref', $participant)
                ->firstOrFail();

            try {
                $ecf = EventCustomField::where('event_id', $_participant->event_id)
                    ->where('slug', 'family_registrations')
                    // ->where('type', 'select'); // It can be of any field type (checkbox or radio)
                    ->firstOrFail();
                try {
                    $_participant->familyRegistrations()->firstOrCreate([
                        // 'participant_id' => $_participant->id,
                        'event_custom_field_id' => $ecf->id,
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'gender' => $request->gender,
                        'dob' => $request->dob
                    ]);
                } catch (QueryException $e) {

                    return $this->error('Unable to create the family member! Please try again.', 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {

                return $this->error('The event custom field was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The participant was not found!', 404);
        }

        return $this->success('Successfully added the family member!', 200, [
            'participant' => new ParticipantResource($_participant->load('familyRegistrations')),
            'genders' => GenderEnum::_options()
        ]);
    }

    /**
     * Edit a family member
     *
     * @urlParam participant_ref string required The participant ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
     * @urlParam familyRegistrationId int required The id of the family registration. Example: 1
     *
     * @param string $participant
     * @param int $familyRegistrationId
     * @return JsonResponse
     */
    public function editFamilyMember(string $participant, int $familyRegistrationId): JsonResponse
    {
        $_participant = Participant::whereHas('participantCustomFields', function ($query) {
            $query->where('key', 'cf_family_registrations')
                ->whereNotNull('value');
        });

        try {
            $_participant = $_participant->where('ref', $participant)
                ->firstOrFail();

            try {
                $familyRegistration =  $_participant->familyRegistrations()
                    ->where('id', $familyRegistrationId)
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {

                return $this->error('The family member was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The participant was not found!', 404);
        }

        return $this->success('Edit the family member!', 200, [
            'family_registration' => $familyRegistration,
            'genders' => GenderEnum::_options()
        ]);
    }

    /**
     * Update the family member
     *
     * @urlParam participant_ref string required The participant ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
     * @urlParam familyRegistrationId int required The id of the family registration. Example: 1
     *
     * @param FamilyRegistrationRequest $request
     * @param string $participant
     * @param int $familyRegistrationId
     * @return JsonResponse
     */
    public function updateFamilyMember(FamilyRegistrationRequest $request, string $participant, int $familyRegistrationId): JsonResponse
    {
        $_participant = Participant::whereHas('participantCustomFields', function ($query) {
            $query->where('key', 'cf_family_registrations')
                ->whereNotNull('value');
        });

        try {
            $_participant = $_participant->where('ref', $participant)
                ->firstOrFail();

            try {
                $familyRegistration = $_participant->familyRegistrations()
                    ->where('id', $familyRegistrationId)
                    ->firstOrFail();

                $familyRegistration = $familyRegistration->update($request->only([
                    'first_name',
                    'last_name',
                    'gender'
                ]));
            } catch (ModelNotFoundException $e) {

                return $this->error('The family member was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The participant was not found!', 404);
        }

        return $this->success('Successfully updated the family member!', 200, [
            'family_registration' => $familyRegistration,
            'genders' => GenderEnum::_options()
        ]);
    }

    /**
     * Delete a family member
     *
     * @urlParam participant_ref string required The participant ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
     * @urlParam familyRegistrationId int required The id of the family registration. Example: 1
     *
     * @param string $participant
     * @param int $familyRegistrationId
     * @return JsonResponse
     */
    public function deleteFamilyMember(string $participant, int $familyRegistrationId): JsonResponse
    {
        $_participant = Participant::whereHas('participantCustomFields', function ($query) {
            $query->where('key', 'cf_family_registrations')
                ->whereNotNull('value');
        });

        try {
            $_participant = $_participant->where('ref', $participant)
                ->firstOrFail();

            try {
                $familyRegistration = $_participant->familyRegistrations()
                    ->where('id', $familyRegistrationId)
                    ->firstOrFail();

                $familyRegistration = $familyRegistration->delete();
            } catch (ModelNotFoundException $e) {
                return $this->error('The family member was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The participant was not found!', 404);
        }

        return $this->success('Successfully deleted the family member!', 200, $familyRegistration);
    }

    /**
     * Export participants
     *
     * @queryParam state string Filter by state. Must be one of live, expired, archived. Example: live
     * @queryParam status string Filter by state. Must be one of paid, complete, notified, incomplete, clearance. Example: complete
     * @queryParam event string Filter by event slug. No-example
     * @queryParam charity string Filter by charity slug. No-example
     * @queryParam via string Filter by added via. Must be one of partner_events, book_events, registration_page, external_enquiry_offer, team_invitation, website. No-example
     * @queryParam gender string Filter by gender. Must be one of male, female. No-example
     * @queryParam tshirt_size string Filter by tshirt_size. Must be one of xxxs, xxs, xs, sm, m, l, xl, xxl, xxxl. No-example
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: first_name:desc,last_name:asc,full_name:desc
     * @queryParam year string Specifying year filter for when user was created. Example: 2023
     * @queryParam period string Specifying a period to filter users creation date by. Example: 24h
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param ParticipantListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(ParticipantListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->participantService->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while exporting participants\' data.', 400);
        }
    }

    /**
     * Notify a participant(s)
     *
     * @param ParticipantNotifyRequest $request
     * @return JsonResponse
     */
    public function notify(ParticipantNotifyRequest $request): JsonResponse
    {
        try {
            $participants = Participant::whereNot('status', ParticipantStatusEnum::Complete) // Only notify the participants that have not completed their registration
                ->whereIn('ref', $request->refs)
                ->get();

            if (!$participants->count()) {
                throw new ModelNotFoundException();
            }

            // $this->dispatch(new ParticipantsNotifyJob($participants));
        } catch (ModelNotFoundException $e) {
            return $this->error('The participants were not found!', 404);
        }

        return $this->success('Successfully notified the participants', 200);
    }

    /**
     * Offer the event place to the participant.
     *
     * @param  ParticipantOfferPlaceRequest  $request
     * @param  string                        $participant
     * @return JsonResponse
     */
    public function offerPlace(ParticipantOfferPlaceRequest $request, string $participant): JsonResponse
    {
        try {
            $_participant = Participant::with(['user'])
                ->where('ref', $participant)
                ->filterByAccess()
                ->whereHas('eventEventCategory.eventCategory', function ($query) {
                    $query->whereHas('site', function ($q) {
                        $q->makingRequest();
                    });
                });

            $_participant = $_participant->firstOrFail();

            if (!$_participant->user) {
                throw new Exception('The user account was soft deleted!');
            }

            try { // CHECK IF THE PARTICIPANT CAN REGISTER
                $eec = EventEventCategory::with(['event', 'eventCategory'])
                    ->where('ref', $request->eec)
                    ->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->firstOrFail();

                try {
                    $request['email'] = $_participant->user->email;

                    DB::beginTransaction();

                    $register = Participant::registerForEvent($request, $eec, ParticipantAddedViaEnum::PartnerEvents);

                    DB::commit();

                    $passed = [
                        'eecs' => [
                            [
                                'id' => $eec->id,
                                'ref' => $eec->ref,
                                'name' => $eec->event->formattedName,
                                'category' => $eec->eventCategory?->name,
                                'reg_status' => $register->status,
                                'registration_fee' => $eec->userRegistrationFee($register->user),
                                'participant' => $register->participant
                            ]
                        ]
                    ];

                    $extraData = [
                        'passed' => $passed,
                        'wasRecentlyCreated' => $register->wasRecentlyCreated
                    ];

                    event(new ParticipantNewRegistrationsEvent($register->user, $extraData, $register->participant->invoiceItem?->invoice, clientSite())); // Notify participant via email
                } catch (QueryException $e) {
                    DB::rollback();
                    return $this->error('Unable to create the participant! Please try again.', 406, $e->getMessage());
                } catch (Exception $e) {
                    DB::rollback();
                    return $this->error($e->getMessage(), 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {
                return $this->error('The event category does not belong to the event!', 404, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The participant was not found!', 404);
        }

        return $this->success('The participant has been offered a place' . ($register->_message ?? null) . '!', 200, new ParticipantResource($register->participant->load(['charity:id,ref,name,slug', 'eventEventCategory.event:id,ref,name,slug', 'eventEventCategory.eventCategory:id,ref,name,slug', 'eventPage', 'user:id,ref,email,first_name,last_name'])));
    }

    /**
     * Save/Update participant custom fields
     *
     * @param  Request        $request
     * @param  Participant    $participant
     * @return void
     */
    private function saveParticipantCustomFields(Request $request, Participant $participant)
    {
        foreach ($request->custom_fields as $key => $value) {
            $eventCustomFieldId = EventCustomField::whereHas('event', function ($query) use ($participant) {
                $query->whereHas('eventCategories', function ($query) use ($participant) {
                    $query->where('event_event_category.ref', $participant->eventEventCategory->ref);
                });
            })->where('slug', $key)
                ->value('id');

            if ($eventCustomFieldId) { // Ensure the custom field exists
                if (isset($value)) { // only create a record when the value is not null (helps to avoid expanding our database with useless data since null returned from the query to check if the record exists is the same as the record beign returned with null on the value property)
                    $participant->participantCustomFields()->updateOrCreate([
                        'event_custom_field_id' => $eventCustomFieldId
                    ], [
                        'value' => $value
                    ]);
                } else { // In case the participant would like to delete the previously saved data on the given optional custom field
                    $participant->participantCustomFields()
                        ->where('event_custom_field_id', $eventCustomFieldId)
                        ->first()?->delete();
                }
            }
        }
    }

    /**
     * Update the participant payment status.
     *
     * @param Request $request
     * @param Participant $_participant
     * @return void
     * @throws Exception
     */
    private function updatePaymentStatus(Request $request, Participant &$_participant)
    {
        if ($request->filled('payment_status')) {
            if ($_participant->payment_status == InvoiceStatusEnum::Paid) {
                throw new \Exception("The participant has a paid invoice attached to them as such their payment status cannot be changed. If you really want to change it, you need to manually delete the invoice and try again!");
            } else if ($_participant->payment_status == ParticipantPaymentStatusEnum::Waived && $_participant->invoiceItem?->invoice && $_participant->invoiceItem?->invoice->status == InvoiceStatusEnum::Paid) {
                throw new \Exception("The waive participant has a paid invoice attached to them as such their payment status cannot be changed. If you really want to change it, you need to ... (TO BE DETERMINED)!");
            }

            if ($request->payment_status == ParticipantPaymentStatusEnum::Waived->value && $request->filled('waive') && $request->filled('waiver')) { // Waive must be set whenever the participant is exempted (particially or fully) from payment.
                if ($_participant->payment_status == InvoiceStatusEnum::Unpaid) { // Delete the invoice item
                    $_participant->invoiceItem?->delete();
                }

                // Set the new payment status
                $_participant->waive = $request->waive;
                $_participant->waiver = $request->waiver;
            } else if ($request->payment_status == ParticipantPaymentStatusEnum::Paid->value) { // Create a paid invoice and attach it to the participant (invoiceItem)
                if (!AccountType::isAdmin()) { // Only the admin can set the payment status to paid
                    throw new \Exception("Only the admin can set the payment status to paid");
                }

                if ($_participant->payment_status == InvoiceStatusEnum::Unpaid) { // Delete the invoice item if it exists
                    // NB: Why deleting the unpaid invoice instead of changing its status to paid?
                    // This is because, by changing the status of this unpaid invoice to paid from the invoice update endpoint, the participant payment status will change to paid. But since the admin did not go through that method/process to update the payment status of the participant, we assume that is not obviously what they intended to do. So, that is why we delete the unpaid invoice and create a new one. The new one is more accurate than the existing unpaid one because the price of the fee_type (local or international) set by them in this request might not be the same as the one in the unpaid invoice. It might happen that they are not aware of the existence of the unpaid invoice while performing this action too. Anyway, we shall explain to them how it works.

                    $_participant->invoiceItem?->delete();
                }

                if ($_participant->payment_status == ParticipantPaymentStatusEnum::Waived) { // Set waive and waiver to null when the payment status changes from waive (exempted) to paid
                    $_participant->waive = null;
                    $_participant->waiver = null;

                    $_participant->invoiceItem?->delete(); // Delete the invoice item associated with the participant. The status of the invoice should be unpaid as the exception above handles the case for waive & paid invoices
                }

                $invoice = $_participant->user->invoices()->create([
                    'name' => Invoice::getFormattedName(InvoiceItemTypeEnum::ParticipantRegistration, null, $_participant),
                    'issue_date' => Carbon::now(),
                    'due_date' => Carbon::now(),
                    'price' => $request->fee_type == FeeTypeEnum::International->value
                        ? $_participant->eventEventCategory->international_fee
                        : ($request->fee_type == FeeTypeEnum::Local->value
                            ? $_participant->eventEventCategory->local_fee
                            : null
                        ),
                    'status' => InvoiceStatusEnum::Paid,
                    'state' => InvoiceStateEnum::Complete,
                    'send_on' => Carbon::now()
                ]);

                $_participant->invoiceItem()->create([
                    'invoice_id' => $invoice->id,
                    'type' => InvoiceItemTypeEnum::ParticipantRegistration,
                    'status' => InvoiceItemStatusEnum::Paid,
                    'price' => $invoice->price
                ]);

                $message = ' (a paid invoice was generated and attached to it';
            } else if ($request->payment_status == ParticipantPaymentStatusEnum::Unpaid->value) {
                if ($_participant->payment_status == ParticipantPaymentStatusEnum::Waived) { // Set waive and waiver to null when the payment status changes from waive (exempted) to unpaid
                    $_participant->waive = null;
                    $_participant->waiver = null;

                    $_participant->invoiceItem?->delete(); // Delete the invoice item associated with the participant. The status of the invoice should be unpaid as the exception above handles the case for waive & paid invoices
                }
            }

            $_participant->save();
        }
    }
}
