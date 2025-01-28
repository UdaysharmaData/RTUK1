<?php

namespace App\Http\Controllers\Portal;

use Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\Request;
use App\Facades\ClientOptions;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\AccountType;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\DefaultQueryParamService;
use App\Services\DataCaching\CacheDataManager;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\Http\Helpers\HeatOptions;

use App\Modules\Participant\Requests\EntryUpdateRequest;
use App\Modules\Participant\Requests\ParticipantDeleteRequest;

use App\Modules\Event\Models\EventCustomField;
use App\Modules\Participant\Models\Participant;

use App\Enums\GenderEnum;
use App\Enums\ListTypeEnum;
use App\Enums\BoolYesNoEnum;
use App\Enums\ProfileEthnicityEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\BoolEnabledDisabledEnum;
use App\Enums\ParticipantPaymentStatusEnum;
use App\Enums\ParticipantProfileTshirtSizeEnum;
use App\Enums\ParticipantProfileWeeklyPhysicalActivityEnum;
use App\Modules\Event\Exceptions\EventEventCategoryException;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Participant\Exceptions\IsRegisteredException;
use App\Modules\Participant\Exceptions\ParticipantTransferException;
use App\Traits\Response;
use App\Traits\SiteTrait;

use App\Traits\UploadTrait;
use App\Traits\DownloadTrait;
use App\Traits\SingularOrPluralTrait;

use App\Modules\Participant\Resources\ParticipantResource;
use App\Modules\Participant\Requests\EntryListingQueryParamsRequest;

use App\Services\DataServices\EntryDataService;
use App\Services\DataServices\ParticipantDataService;
use App\Services\DataServices\PartnerEventDataService;

/**
 * @group Entries
 * Manages participants entries (registrations) on the application
 * @authenticated
 */
class EntryController extends Controller
{
    use Response,
        SiteTrait,
        UploadTrait,
        DownloadTrait,
        SingularOrPluralTrait;

    /*
    |--------------------------------------------------------------------------
    | Entries Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with participants entries. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected EntryDataService $entryDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_registrations', [
            'except' => []
        ]);
    }

    /**
     * Get the events of the user (having the participant role)
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam category string Filter by event category slug. No-example
     * @queryParam status string Filter by participant status. No-example
     * @queryParam year int Filter by year. No-example
     * @queryParam month int Filter by month. No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: charity:desc,event:asc,status:desc,created_at:desc
     * @queryParam period string Specifying a period to filter users creation date by. Example: 24h
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param EntryListingQueryParamsRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(EntryListingQueryParamsRequest $request): JsonResponse
    {
        if (!AccountType::isParticipant()) { // Only participants have access to this
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $entries = (new CacheDataManager(
            $this->entryDataService,
            'getPaginatedList',
            [$request]
        ))->getData();

        return $this->success('The list of participant\'s entries', 200, [
            'entries' => new ParticipantResource($entries),
            'options' => ClientOptions::only('entries', [
                'months',
                'statuses',
                'years',
                'periods',
                'order_by',
                'order_direction'
            ]),
            'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Entries))->getDefaultQueryParams()
        ]);
    }

    /**
     * Edit an entry
     *
     * @urlParam participant_ref string required The ref of the participant. Example: 97d40d8e-9d33-4f80-9f07-78aea6a59039
     *
     * @param string $participant
     * @return JsonResponse
     * @throws Exception
     */
    public function edit(string $participant): JsonResponse
    {
        try {
            $participant = (new CacheDataManager(
                $this->entryDataService,
                'edit',
                [$participant]
            ))->getData();
        } catch (ModelNotFoundException $e) {
            return $this->error('The entry was not found!', 404);
        }

        return $this->success('Edit the entry', 200, [
            'participant' => new ParticipantResource($participant),
            'genders' => GenderEnum::_options(),
            'tshirt_sizes' => ParticipantProfileTshirtSizeEnum::_options(),
            'payment_statuses' => ParticipantPaymentStatusEnum::_options(),
            'speak_with_coach' => BoolYesNoEnum::_options(),
            'hear_from_partner_charity' => BoolYesNoEnum::_options(),
            'ethnicities' => ProfileEthnicityEnum::_options(),
            'weekly_physical_activities' => ParticipantProfileWeeklyPhysicalActivityEnum::_options(),
            'waives' => ParticipantWaiveEnum::_options(),
            'waivers' => ParticipantWaiverEnum::_options(),
            'heat_options' => HeatOptions::generate(),
            'histories' => BoolYesNoEnum::_options(),
            'family_registrations' => BoolEnabledDisabledEnum::_options()
        ]);
    }

    /**
     * Update an entry
     *
     * @urlParam participant_ref string required The ref of the participant. Example: 97d40d8e-9d33-4f80-9f07-78aea6a59039
     *
     * @param EntryUpdateRequest $request
     * @param Participant $participant
     * @return JsonResponse
     * @throws Exception
     */
    public function update(EntryUpdateRequest $request, Participant $participant): JsonResponse
    {
        $_participant = Participant::with(['invoiceItem.invoice.upload', 'participantCustomFields.eventCustomField', 'participantExtra', 'user.profile.participantProfile'])
            ->where('ref', $participant->ref)
            ->filterByAccess();

        $_participant = $_participant->whereHas('eventEventCategory.eventCategory', function ($query) {
            $query->whereHas('site', function ($q) {
                $q->makingRequest();
            });
        });

        try {
            $_participant = $_participant->firstOrFail();
            DB::table('profiles')->where('user_id', $_participant->user_id)->update(['gender' => $request->gender]);
            DB::table('users')->where('id', $_participant->user_id)->update(['phone' => $request->phone]);

            try {
                DB::beginTransaction();

                $_participant->fill($request->only(['preferred_heat_time', 'raced_before', 'estimated_finish_time', 'enable_family_registration', 'speak_with_coach', 'hear_from_partner_charity', 'reason_for_participating']));

                $_participant->save();

                $profileData = [];
                $participantProfileData = [];

                if ($_participant->participantExtra) {
                    $_profileData = $request->only(['profile.dob',  'profile.gender', 'profile.ethnicity']);
                    $_profileData = $profileData['profile'] ?? [];
                    $_participant->participantExtra->update([...$request->only(['first_name', 'last_name', 'dob', 'phone', 'gender', 'ethnicity', 'weekly_physical_activity']), ...$_profileData]);
                } else {
                    // Update the user's info
                    $_participant->user()->update($request->only(['first_name', 'last_name', 'phone']));

                    // Set these properties here to ensure the database only gets hitted once.
                    $profileData = ['dob', 'profile.gender', 'profile.ethnicity'];
                    $participantProfileData = ['weekly_physical_activity'];
                }

                // Update the user's profile
                $profileData = $request->only([...$profileData, 'profile.state', 'profile.address', 'profile.city', 'profile.country', 'profile.postcode', 'profile.nationality', 'profile.occupation', 'profile.passport_number']);
                $_participant->user->profile()->updateOrCreate([], $profileData['profile'] ?? []); // Update the user's profile (contains the event registration/required fields)

                $_participant->user->profile()->updateOrCreate([], $profileData ?? []); 

                // Update participant profile
                $_participant->load('user.profile');
                $_participant->user->profile->participantProfile()->updateOrCreate([], $request->only([...$participantProfileData, 'slogan', 'club', 'emergency_contact_name', 'emergency_contact_phone', 'tshirt_size']));

                if ($request->filled('custom_fields')) { // Update the participant custom fields
                    $this->saveParticipantCustomFields($request, $_participant);
                }

                DB::commit(); // Persist the data before validating the participant data required by the event

                $_participant->refresh(); // Refresh the participant

                $_participant->canCompleteRegistration(); // Check if the participant can complete their registration. Throws exception otherwise

                // Validate event registration/required & custom fields and return a warning if the participant has not filled all the fields required for the event
                $result = Participant::updateParticipantStatus($_participant);

                CacheDataManager::flushAllCachedServiceListings($this->entryDataService);
                CacheDataManager::flushAllCachedServiceListings(new ParticipantDataService());
                (new CacheDataManager(new PartnerEventDataService(), 'participants'))->flushCachedServiceListings();

                if (!$result->status) {
                    return $this->success('Registration successfully updated! But there are warnings.', 200, [
                        'errors' => $result->errors,
                        'participant' => new ParticipantResource($_participant->load(['charity:id,ref,name,slug', 'eventEventCategory.event:id,ref,name,slug', 'eventEventCategory.event.eventCustomFields', 'eventEventCategory.eventCategory:id,ref,name,slug', 'participantCustomFields.eventCustomField', 'user.profile.participantProfile']))
                    ]);
                }
                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                CacheDataManager::flushAllCachedServiceListings($this->entryDataService);
                CacheDataManager::flushAllCachedServiceListings(new ParticipantDataService());
                (new CacheDataManager(new PartnerEventDataService(), 'participants'))->flushCachedServiceListings();

                return $this->error('Unable to update the entry! Please try again.', 406, $e->getMessage());
            } catch (Exception $e) {
                CacheDataManager::flushAllCachedServiceListings($this->entryDataService);
                CacheDataManager::flushAllCachedServiceListings(new ParticipantDataService());
                (new CacheDataManager(new PartnerEventDataService(), 'participants'))->flushCachedServiceListings();

                return $this->error($e->getMessage(), 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The entry was not found!', 404);
        }

        return $this->success('Successfully updated the entry!', 200, new ParticipantResource($_participant->load(['charity:id,ref,name,slug', 'eventEventCategory.event:id,ref,name,slug', 'eventEventCategory.event.eventCustomFields', 'eventEventCategory.eventCategory:id,ref,name,slug', 'participantCustomFields.eventCustomField', 'user.profile.participantProfile'])));
    }

    /**
     * Check if the participant can be transferred to another event
     * 
     * @urlParam participant_ref string required The ref of the participant. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @bodyParam eec string required The event event category ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
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
                    $fail('The selected event and category are invalid.');
                }
            }]
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 406);
        }

        try {
            $participant = Participant::with('user')
                ->filterByAccess()
                ->where('ref', $participant)
                ->whereHas('eventEventCategory.eventCategory', function ($query) {
                    $query->whereHas('site', function ($q) {
                        $q->makingRequest();
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
            Log::channel(static::getSite()?->code . 'adminanddeveloper')->info('Participant Transfer Exception.  - An error occurred while verifying your entry transfer! - ' . $e->getMessage());
            return $this->error('An error occurred while verifying your entry transfer!', 406, $e->getMessage());
        }

        $message = $result['message'];
        unset($result['message']);

        return $this->success($message, 200, $result);
    }

    /**
     * Transfer the participant to another event
     * 
     * @urlParam participant_ref string required The participant ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
    //  * @queryParam eec string required The eec ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9 // For emails - Transfer initiated by admin
     * @bodyParam eec string required The event event category ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
     *
     * @param  mixed         $request
     * @param  string        $participant
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
                    $fail('The selected event is invalid.');
                }
            }]
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 406);
        }

        try {
            $participant = Participant::with(['invoiceItem.invoice.upload', 'user'])
                ->filterByAccess()
                ->where('ref', $participant)
                ->whereHas('eventEventCategory.eventCategory', function ($query) {
                    $query->whereHas('site', function ($q) {
                        $q->makingRequest();
                    });
                })->firstOrFail();

            $eec = EventEventCategory::where('ref', $request->eec)->first();

            $result = Participant::transfer($participant, $eec, $request->user());
        } catch (ModelNotFoundException $e) {
            return $this->error('The entry was not found!', 404);
        } catch (ParticipantTransferException $e) {
            return $this->error($e->getMessage(), 406, $e->errorData);
        } catch (EventEventCategoryException $e) {
            return $this->error($e->getMessage(), 406);
        } catch (IsRegisteredException $e) {
            return $this->error($e->getMessage(), 406);
        } catch (Exception $e) {
            Log::channel(static::getSite()?->code . 'adminanddeveloper')->info('Participant Transfer Exception.  - An error occurred while transfering your entry! - ' . $e->getMessage());
            return $this->error('An error occurred while transfering your entry!', 406, $e->getMessage());
        }

        $message = $result['message'];
        unset($result['message']);

        return $this->success($message, 200, $result);
    }

    /**
     * Download an entry
     *
     * @urlParam participant_ref string required The participant ref. Example: 975df0ab-6954-4636-8792-fd242aeb7ee9
     *
     * @param  string  $participant
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function download(string $participant): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        $participant = Participant::with(['invoiceItem.invoice.upload'])
            ->where('ref', $participant)
            ->filterByAccess();

        $participant = $participant->whereHas('eventEventCategory.eventCategory', function ($query) {
            $query->whereHas('site', function ($q) {
                $q->makingRequest();
            });
        });

        try {
            $participant = $participant->firstOrFail();

            if ($participant->invoiceItem?->invoice?->upload?->url && Storage::disk(config('filesystems.default'))->exists($participant->invoiceItem->invoice->upload->url)) {
                $fileName = $participant->invoiceItem->invoice->name . '.pdf';
                $fileName = str_replace(array("/", "\\", ":", "*", "?", "«", "<", ">", "|"), "-", $fileName);

                $path = $participant->invoiceItem?->invoice?->upload?->url;

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
                    $undeletedParticipants[] = $participant->user->full_name . " - " . $deleteResult->message;
                }
                if (!empty($undeletedParticipants)) {
                    $errorMessage = implode('<br/>', $undeletedParticipants);
                    return $this->error($errorMessage, 400);
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('An error occurs while trying to delete your entry', 406);
        }

        return $this->success('Successfully deleted the ' . static::singularOrPlural(['entry', 'entries'], $request->refs) . '!', 200, new ParticipantResource($participants));
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
}
