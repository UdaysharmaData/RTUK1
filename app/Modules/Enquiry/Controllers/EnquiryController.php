<?php

namespace App\Modules\Enquiry\Controllers;

use DB;
use Log;
use Exception;
use App\Facades\ClientOptions;
use App\Http\Helpers\AccountType;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Http\Helpers\EmailAddressHelper;
use App\Services\DefaultQueryParamService;
use App\Events\ParticipantNewRegistrationsEvent;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Enums\GenderEnum;
use App\Enums\FeeTypeEnum;
use App\Enums\ListTypeEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Enums\ParticipantPaymentStatusEnum;

use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;

use App\Modules\Enquiry\Requests\EnquiryCreateRequest;
use App\Modules\Enquiry\Requests\EnquiryUpdateRequest;
use App\Modules\Enquiry\Requests\EnquiryDeleteRequest;
use App\Modules\Enquiry\Requests\EnquiryRestoreRequest;
use App\Modules\Enquiry\Requests\EnquiryOfferPlaceRequest;
use App\Modules\Enquiry\Requests\EnquiryListingQueryParamsRequest;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;
use App\Traits\DownloadTrait;
use App\Traits\SingularOrPluralTrait;
use App\Modules\Enquiry\Traits\EnquiryTrait;
use App\Services\ClientOptions\Traits\Options;

use App\Modules\Enquiry\Resources\EnquiryResource;
use App\Modules\Participant\Resources\ParticipantResource;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EnquiryDataService;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;

/**
 * @group Enquiries
 * Manages website enquiries on the application
 * @authenticated
 */
class EnquiryController extends Controller
{
    use Response,
        SiteTrait,
        UploadTrait,
        DownloadTrait,
        SingularOrPluralTrait,
        EnquiryTrait,
        Options;

    /*
    |--------------------------------------------------------------------------
    | Enquiry Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with website enquiries. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected EnquiryDataService $enquiryDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_enquiries');
    }

    /**
     * The list of enquiries
     *
     * @queryParam charity string Filter by charity ref. No-example
     * @queryParam event string Filter by event ref. No-example
     * @queryParam corporate string Filter by corporate ref. No-example
     * @queryParam site string Filter by site ref. No-example
     * @queryParam year int Filter by year. No-example
     * @queryParam month int Filter by month. No-example
     * @queryParam status string Filter by status. Must be one of processed, pending. Example: processed
     * @queryParam action string Filter by action. Must be one of register_failed_eec_exhausted_places, registration_failed_charity_places_exhausted
     * @queryParam converted bool Filter by converted. No-example
     * @queryParam contacted bool Filter by contacted. No-example
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: first_name:desc,last_name:asc,full_name:desc
     * @queryParam year string Specifying year filter for when user was created. Example: 2023
     * @queryParam period string Specifying a period to filter users creation date by. Example: 24h
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * @queryParam site string Filter by site ref. The site to search in. This parameter is only available to users of role administrator when making requests from sportsmediaagency.com (That is, when making request to get data of the whole application - all the platforms). No-example
     *
     * @param  EnquiryListingQueryParamsRequest  $request
     * @return JsonResponse
     */
    public function index(EnquiryListingQueryParamsRequest $request): JsonResponse
    {
        $enquiries = (new CacheDataManager(
            $this->enquiryDataService,
            'getPaginatedList',
            [$request]
        ))->getData();

        return $this->success('The list of enquiries', 200, [
            'enquiries' => new EnquiryResource($enquiries),
            'options' => ClientOptions::only('enquiries', [
                'statuses',
                'actions',
                'months',
                'contacted',
                'converted',
                'order_by',
                'deleted',
                'order_direction',
                'years',
                'periods',
            ]),
            'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Enquiries))->getDefaultQueryParams()
        ]);
    }

    /**
     * Create an enquiry
     * 
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return $this->success('Create an enquiry!', 200, [
            'genders' => GenderEnum::_options()
        ]);
    }

    /**
     * Store an enquiry
     *
     * @param EnquiryCreateRequest $request
     * @return JsonResponse
     */
    public function store(EnquiryCreateRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $enquiry = new Enquiry();
            $enquiry->fill($request->all());

            if ($request->filled('site')) {
                $enquiry->site()->associate(Site::where('ref', $request->site)->first());
            }

            if ($request->filled('charity')) {
                $enquiry->charity()->associate(Charity::where('ref', $request->charity)->first());
            }

            if ($request->filled('event')) {
                $event = Event::withoutAppends()->where('ref', $request->event)->select('id', 'ref', 'slug', 'name')->first();
                $enquiry->event()->associate($event);
            }

            if ($request->filled('event_category')) {
                $enquiry->eventCategory()->associate(EventCategory::where('ref', $request->event_category)->first());
            }

            if ($request->filled('external_enquiry')) {
                $enquiry->externalEnquiry()->associate(ExternalEnquiry::where('ref', $request->external_enquiry)->first());
            }

            $enquiry->save();

            DB::commit();
        } catch (QueryException $e) {
            DB::rollback();
            return $this->error('Unable to create the enquiry! Please try again', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();
            return $this->error('Unable to create the enquiry! Please try again', 406, $e->getMessage());
        }

        return $this->success('Successfully created the enquiry!', 200, new EnquiryResource($enquiry));
    }

    /**
     * Edit an enquiry
     * 
     * @urlParam enquiry_ref string required The ref of the enquiry. Example: 97c0304a-a320-4320-a37a-40f7ab32b525
     * 
     * @param  string $ref
     * @return JsonResponse
     */
    public function edit(string $ref): JsonResponse
    {
        try {
            $enquiry = (new CacheDataManager(
                $this->enquiryDataService,
                'edit',
                [$ref]
            ))->getData();
        } catch (ModelNotFoundException $e) {
            return $this->error('The enquiry was not found!', 404);
        }

        return $this->success('Edit the enquiry!', 200, [
            'charity_conflict' => $this->charityConflict($enquiry),
            'offer_charity' => $this->offerCharity($enquiry),
            'enquiry' => new EnquiryResource($enquiry),
            'genders' => GenderEnum::_options(),
            'offer_place_payment_statuses' => ParticipantPaymentStatusEnum::_options([ParticipantPaymentStatusEnum::Refunded, ParticipantPaymentStatusEnum::Transferred]),
            'waives' => ParticipantWaiveEnum::_options(),
            'waivers' => ParticipantWaiverEnum::_options(),
            'fee_types' => FeeTypeEnum::_options()
        ]);
    }

    /**
     * Update an enquiry
     * 
     * @urlParam enquiry_ref string required The ref of the enquiry. Example: 97c0304a-a320-4320-a37a-40f7ab32b525
     * 
     * @param  EnquiryUpdateRequest  $request
     * @param  string $ref
     * @return JsonResponse
     */
    public function update(EnquiryUpdateRequest $request, string $ref): JsonResponse
    {
        try {
            $_enquiry = Enquiry::whereHas('site', function ($query) {
                $query->makingRequest();
            })->where('ref', $ref)
                ->filterByAccess()
                ->firstOrFail();

            try {
                if ($request->filled('site')) {
                    $request['site_id'] = Site::where('ref', $request->site)->value('id');
                }

                if ($request->filled('charity')) {
                    $request['charity_id'] = Charity::where('ref', $request->charity)->value('id');
                }

                if ($request->filled('event')) {
                    $request['event_id'] = Event::where('ref', $request->event)->value('id');
                }

                if ($request->filled('event_category')) {
                    $request['event_category_id'] = EventCategory::where('ref', $request->event_category)->value('id');
                }

                if ($request->filled('external_enquiry')) {
                    $request['external_enquiry_id'] = ExternalEnquiry::where('ref', $request->external_enquiry)->value('id');
                }

                $_enquiry->update($request->all());

                if ($_enquiry->wasChanged('contacted')) {
                    $timeline = $_enquiry->timeline;
                    $timeline[] = ['caption' => 'Contacted', 'value' => $_enquiry->contacted, 'datetime' => $_enquiry->updated_at];
                    $_enquiry->timeline = $timeline;
                    $_enquiry->save();
                }

                if ($_enquiry->wasChanged('converted')) {
                    $timeline = $_enquiry->timeline;
                    $timeline[] = ['caption' => 'Converted', 'value' => $_enquiry->converted, 'datetime' => $_enquiry->updated_at];
                    $_enquiry->timeline = $timeline;
                    $_enquiry->save();
                }
            } catch (QueryException $e) {
                return $this->error('Unable to update the enquiry!', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The enquiry was not found!', 404);
        }

        $enquiry = $_enquiry->load([
            'charity:id,ref,name,slug',
            'event' => function($query) {
                $query->withoutAppends()->withOnly([])->select('id', 'ref', 'name', 'slug');
            },
            'eventCategory',
            'externalEnquiry',
            'site',
            'participant' => function ($query) {
                $query->AppendsOnly([
                    'formatted_status'
                ])->withOnly([
                    'eventEventCategory.event' => function ($query) {
                        $query->withoutAppends()->withOnly([])->select('id', 'ref', 'name', 'slug');
                    },
                    'eventEventCategory.eventCategory',
                    'user',
                    'charity:id,ref,name,slug'
                ]);
            },
            'user.charityUser.charity:id,ref,name,slug'
        ]);

        return $this->success('Successfully updated the enquiry!', 200, [
            'charity_conflict' => $this->charityConflict($enquiry),
            'offer_charity' => $this->offerCharity($enquiry),
            'enquiry' => new EnquiryResource($enquiry)
        ]);
    }

    /**
     * Delete one or many enquiries
     *
     * @param  EnquiryDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(EnquiryDeleteRequest $request): JsonResponse
    {
        try {
            Enquiry::whereHas('site', function ($query) use ($request) {
                $query->makingRequest();
            })->filterByAccess()->whereIn('ref', $request->refs)->delete();

            CacheDataManager::flushAllCachedServiceListings($this->enquiryDataService);
        } catch (Exception $e) {
            Log::error($e);
            return $this->error("An error occurs while trying to delete enquiries", 400);
        }

        return $this->success('Successfully deleted the ' . static::singularOrPlural(['enquiry', 'enquiries'], $request->refs), 200);
    }

    /**
     * Restore one or many enquiries
     *
     * @param  EnquiryRestoreRequest $request
     * @return JsonResponse
     */
    public function restore(EnquiryRestoreRequest $request): JsonResponse
    {
        try {
            Enquiry::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->whereIn('ref', $request->refs)->onlyTrashed()->restore();

            CacheDataManager::flushAllCachedServiceListings($this->enquiryDataService);
        } catch (Exception $e) {
            Log::error($e);
            return $this->error("An error occurs while trying to restore enquiries", 400);
        }

        return $this->success('Successfully restored the ' . static::singularOrPlural(['enquiry', 'enquiries'], $request->refs), 200);
    }

    /**
     * Delete one or many enquiries (Permanently)
     * Only the administrator can delete an enquiry permanently.
     *
     * @param  EnquiryDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(EnquiryDeleteRequest $request): JsonResponse
    {
        if (!AccountType::isAdmin()) { // Only the administrator can delete an enquiry permanently.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        try {
            Enquiry::whereHas('site', function ($query) use ($request) {
                $query->makingRequest();
            })->filterByAccess()->whereIn('ref', $request->refs)->onlyTrashed()->forceDelete();

            CacheDataManager::flushAllCachedServiceListings($this->enquiryDataService);
        } catch (Exception $e) {
            Log::error($e);
            return $this->error("An error occurs while trying to permanently delete enquiries", 400);
        }

        return $this->success('Successfully deleted the ' . static::singularOrPlural(['enquiry', 'enquiries'], $request->refs) . ' permanently', 200);
    }

    /**
     * Export enquiries
     *
     * @queryParam charity string Filter by charity ref. No-example
     * @queryParam event string Filter by event ref. No-example
     * @queryParam corporate string Filter by corporate ref. No-example
     * @queryParam site string Filter by site ref. No-example
     * @queryParam year int Filter by year. No-example
     * @queryParam month int Filter by month. No-example
     * @queryParam status string Filter by status. Must be one of processed, pending. Example: processed
     * @queryParam action string Filter by action. Must be one of register_failed_eec_exhausted_places, registration_failed_charity_places_exhausted
     * @queryParam converted bool Filter by converted. No-example
     * @queryParam contacted bool Filter by contacted. No-example
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: first_name:desc,last_name:asc,full_name:desc
     * @queryParam year string Specifying year filter for when user was created. Example: 2023
     * @queryParam period string Specifying a period to filter users creation date by. Example: 24h
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * @queryParam site string Filter by site ref. The site to search in. This parameter is only available to users of role administrator when making requests from sportsmediaagency.com (That is, when making request to get data of the whole application - all the platforms). No-example
     * 
     * @param  EnquiryListingQueryParamsRequest  $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(EnquiryListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->enquiryDataService->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while exporting enquiries\' data.', 400);
        }
    }

    /**
     * Offer the event place to the enquirer.
     * 
     * @param  EnquiryOfferPlaceRequest  $request
     * @param  string $ref
     * @return JsonResponse
     */
    public function offerPlace(EnquiryOfferPlaceRequest $request, string $ref): JsonResponse
    {
        try {
            $enquiry = Enquiry::with(['event.eventCategories', 'eventCategory'])
                ->where('ref', $ref)
                ->filterByAccess()
                ->firstOrFail();

            if ($enquiry->participant_id)
                throw new \Exception('The enquiry has already been offered a place and converted to a participant!');

            if (!EmailAddressHelper::isValid($enquiry->email))
                throw new \Exception('The email address is invalid!');

            try { // CHECK IF THE PARTICIPANT CAN REGISTER
                $eec = EventEventCategory::with(['event', 'eventCategory'])
                    ->where('ref', $request->eec)
                    ->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->firstOrFail();

                try {
                    $request['email'] = $enquiry->email;
                    $request['first_name'] = $enquiry->first_name ?? "";
                    $request['last_name'] = $enquiry->last_name ?? "";

                    DB::beginTransaction();

                    $register = Participant::registerForEvent($request, $eec, ParticipantAddedViaEnum::Website);

                    // Update the external enquiry
                    $enquiry->participant_id = $register->participant->id;
                    $enquiry->converted = true;
                    $timeline = $enquiry->timeline;
                    $timeline[] = ['caption' => 'Converted', 'value' => $enquiry->converted, 'datetime' => $enquiry->updated_at];
                    $enquiry->timeline = $timeline;
                    $enquiry->save();

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

                    event(new ParticipantNewRegistrationsEvent($register->user, $extraData, $register->participant->invoiceItem?->invoice, clientSite(), $enquiry)); // Notify participant via email
                } catch (QueryException $e) {
                    DB::rollback();
                    return $this->error('Unable to create the participant! Please try again.', 406, $e->getMessage());
                } catch (\Exception $e) {
                    DB::rollback();
                    return $this->error($e->getMessage(), 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {
                return $this->error('The event category does not belongs to the event!', 404, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The enquiry was not found!', 404);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $this->success('The enquiry has been offered a place' . ($register->_message ?? null) . '!', 200, new ParticipantResource($register->participant->load(['charity', 'eventEventCategory.event', 'eventEventCategory.eventCategory', 'eventPage', 'participantCustomFields', 'user.profile.participantProfile'])));
    }
}
