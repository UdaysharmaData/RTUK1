<?php

namespace App\Modules\Enquiry\Controllers;

use DB;
use App\Http\Helpers\AccountType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Http\Helpers\EmailAddressHelper;
use App\Services\DefaultQueryParamService;
use App\Events\ParticipantNewRegistrationsEvent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Enums\GenderEnum;
use App\Enums\FeeTypeEnum;
use App\Enums\ListTypeEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Enums\ParticipantPaymentStatusEnum;

use App\Facades\ClientOptions;
use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\Charity;
use App\Modules\Partner\Models\PartnerChannel;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Event\Models\EventCategoryEventThirdParty;

use App\Modules\Enquiry\Resources\ExternalEnquiryResource;
use App\Modules\Participant\Resources\ParticipantResource;
use App\Modules\Enquiry\Requests\ExternalEnquiryCreateRequest;
use App\Modules\Enquiry\Requests\ExternalEnquiryUpdateRequest;
use App\Modules\Enquiry\Requests\ExternalEnquiryDeleteRequest;
use App\Modules\Enquiry\Requests\ExternalEnquiryOfferPlaceRequest;
use App\Modules\Enquiry\Requests\ExternalEnquiryListingQueryParamsRequest;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;
use App\Traits\DownloadTrait;
use App\Traits\SingularOrPluralTrait;
use App\Services\ClientOptions\Traits\Options;
use App\Modules\Enquiry\Traits\EnquiryTrait;

use App\Http\Helpers\ExternalEnquiryHelper;
use App\Modules\Enquiry\Requests\ExternalEnquiryRestoreRequest;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\ExternalEnquiryDataService;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group External Enquries
 * Manages external enquiries on the application
 * @authenticated
 */
class ExternalEnquiryController extends Controller
{
    use Response,
        SiteTrait,
        UploadTrait,
        DownloadTrait,
        SingularOrPluralTrait,
        EnquiryTrait, Options;

    /*
    |--------------------------------------------------------------------------
    | External Enquiry Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with external enquiries. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected ExternalEnquiryDataService $enquiryDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_enquiries');
    }

    /**
     * The list of external enquiries
     *
     * @queryParam charity string Filter by charity ref. No-example
     * @queryParam event string Filter by event ref. No-example
     * @queryParam year int Filter by year. No-example
     * @queryParam month int Filter by month. No-example
     * @queryParam status string Filter by status. Must be one of pending, processed. Example: pending
     * @queryParam partner string Filter by partner ref. No-example
     * @queryParam channel string Filter by partner channel ref. No-example
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
     * @param  ExternalEnquiryListingQueryParamsRequest  $request
     * @return JsonResponse
     */
    public function index(ExternalEnquiryListingQueryParamsRequest $request): JsonResponse
    {
        $enquiries = (new CacheDataManager(
            $this->enquiryDataService,
            'getPaginatedList',
            [$request]
        ))->getData();

        return $this->success('The list of external enquiries', 200, [
            'enquiries' => new ExternalEnquiryResource($enquiries),
            'options' => ClientOptions::only('external_enquiries', [
                'order_by',
                'deleted',
                'periods',
                'months',
                'order_direction',
                'years',
                'statuses',
                'contacted',
                'converted'
            ]),
            'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::ExternalEnquiries))->getDefaultQueryParams()
        ]);
    }

    /**
     * Create an external enquiry
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return $this->success('Create an external enquiry!', 200, [
            'genders' => GenderEnum::_options()
        ]);
    }

    /**
     * Store an external enquiry
     *
     * @param ExternalEnquiryCreateRequest $request
     * @return JsonResponse
     */
    public function store(ExternalEnquiryCreateRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $enquiry = new ExternalEnquiry();
            $enquiry->fill($request->all());

            if ($request->filled('site')) {
                $enquiry->site()->associate(Site::where('ref', $request->site)->first());
            }

            if ($request->filled('charity')) {
                $enquiry->charity()->associate(Charity::where('ref', $request->charity)->first());
            }

            if ($request->filled('event')) {
                $enquiry->event()->associate(Event::where('ref', $request->event)->first());
            }

            if ($request->filled('partner_channel')) {
                $enquiry->partnerChannel()->associate(PartnerChannel::where('ref', $request->partner_channel)->first());
            }

            if ($request->filled('event_category_event_third_party')) {
                $enquiry->eventCategoryEventThirdParty()->associate(EventCategoryEventThirdParty::where('ref', $request->event_category_event_third_party)->first());
            }

            $now = now();
            $timeline = [ // Set the timeline
                ['caption' => 'Enquiry Received', 'value' => 'true', 'datetime' => $now]
            ];
            $enquiry->created_at = $now;
            $enquiry->updated_at = $now;
            $enquiry->timeline = $timeline;

            $enquiry->save();

            DB::commit();
        } catch (QueryException $e) {
            DB::rollback();
            return $this->error('Unable to create the external enquiry! Please try again', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();
            return $this->error('Unable to create the external enquiry! Please try again', 406, $e->getMessage());
        }

        return $this->success('Successfully created the external enquiry!', 200, new ExternalEnquiryResource($enquiry));
    }

    /**
     * Edit an external enquiry
     *
     * @urlParam enquiry_ref string required The ref of the external enquiry. Example: 97c0304a-a320-4320-a37a-40f7ab32b525
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
            return $this->error('The external enquiry was not found!', 404);
        }

        return $this->success('Edit the external enquiry!', 200, [
            'charity_conflict' => $this->charityConflict($enquiry),
            'offer_charity' => $this->offerCharity($enquiry),
            'enquiry' => new ExternalEnquiryResource($enquiry),
            'genders' => GenderEnum::_options(),
            'offer_place_payment_statuses' => ParticipantPaymentStatusEnum::_options([ParticipantPaymentStatusEnum::Refunded, ParticipantPaymentStatusEnum::Transferred]),
            'waives' => ParticipantWaiveEnum::_options(),
            'waivers' => ParticipantWaiverEnum::_options(),
            'fee_types' => FeeTypeEnum::_options()
        ]);
    }

    /**
     * Update an external enquiry
     *
     * @urlParam enquiry_ref string required The ref of the external enquiry. Example: 97c0304a-a320-4320-a37a-40f7ab32b525
     *
     * @param  ExternalEnquiryUpdateRequest  $request
     * @param  string $ref
     * @return JsonResponse
     */
    public function update(ExternalEnquiryUpdateRequest $request, string $ref): JsonResponse
    {
        try {
            $_enquiry = ExternalEnquiry::whereHas('site', function ($query) {
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

                if ($request->filled('partner_channel')) {
                    $request['partner_channel_id'] = PartnerChannel::where('ref', $request->partner_channel)->value('id');
                }

                if ($request->filled('event_category_event_third_party')) {
                    $request['event_category_event_third_party_id'] = EventCategoryEventThirdParty::where('ref', $request->event_category_event_third_party)->value('id');
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
                return $this->error('Unable to update the external enquiry!', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The external enquiry was not found!', 404);
        }

        $_enquiry = $_enquiry->load([
            'charity:id,ref,name', 'event:id,ref,slug,name', 'event.eventCategories', 'event.eventThirdParties' => function ($query) {
                $query->with(['eventCategories', 'partnerChannel'])
                    ->whereNotNull('external_id')
                    ->whereHas('partnerChannel', function ($query) {
                        $query->whereHas('partner', function ($query) {
                            $query->whereHas('site', function ($query) {
                                $query->makingRequest();
                            });
                        });
                    });
            }, 'eventCategoryEventThirdParty.eventCategory', 'eventCategoryEventThirdParty.eventThirdParty',
            'participant' => function ($query) {
                $query->AppendsOnly([
                    'formatted_status'
                ])->withOnly([
                    'eventEventCategory.event' => function ($query) {
                        $query->withoutAppends()->withOnly([])->select('id', 'ref', 'slug', 'name');
                    },
                    'eventEventCategory.eventCategory',
                    'user',
                    'charity:id,ref,name'
                ]);
            },
            'partnerChannel',
            'user.charityUser.charity'
        ]);

        return $this->success('Successfully updated the external enquiry!', 200, [
            'charity_conflict' => $this->charityConflict($_enquiry),
            'offer_charity' => $this->offerCharity($_enquiry),
            'enquiry' => new ExternalEnquiryResource($_enquiry)
        ]);
    }

    /**
     * Delete one or many external enquiries
     *
     * @param  ExternalEnquiryDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(ExternalEnquiryDeleteRequest $request): JsonResponse
    {
        try {
            ExternalEnquiry::whereHas('site', function ($query) use ($request) {
                $query->makingRequest();
            })->filterByAccess()->whereIn('ref', $request->refs)->delete();

            CacheDataManager::flushAllCachedServiceListings($this->enquiryDataService);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to delete external enquiries.', 400);
        }

        return $this->success('Successfully deleted the ' . static::singularOrPlural(['enquiry', 'enquiries'], $request->refs), 200);
    }

    /**
     * Restore one or many external enquiries
     *
     * @param  ExternalEnquiryRestoreRequest $request
     * @return JsonResponse
     */
    public function restore(ExternalEnquiryRestoreRequest $request): JsonResponse
    {
        try {
            ExternalEnquiry::whereHas('site', function ($query) {
                $query->hasAccess()->makingRequest();
            })->whereIn('ref', $request->refs)->onlyTrashed()->restore();

            CacheDataManager::flushAllCachedServiceListings($this->enquiryDataService);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to restore external enquiries.', 400);
        }

        return $this->success('Successfully restored the ' . static::singularOrPlural(['enquiry', 'enquiries'], $request->refs), 200);
    }

    /**
     * Delete one or many enquiries (Permanently)
     * Only the administrator can delete an enquiry permanently.
     *
     * @param  ExternalEnquiryDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(ExternalEnquiryDeleteRequest $request): JsonResponse
    {
        if (!AccountType::isAdmin()) { // Only the administrator can delete an enquiry permanently.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        try {
            ExternalEnquiry::whereHas('site', function ($query) use ($request) {
                $query->makingRequest();
            })->filterByAccess()->whereIn('ref', $request->refs)
                ->onlyTrashed()->forceDelete();

            CacheDataManager::flushAllCachedServiceListings($this->enquiryDataService);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to delete external enquiries.', 400);
        }

        return $this->success('Successfully deleted the ' . static::singularOrPlural(['enquiry', 'enquiries'], $request->refs) . ' permanently', 200);
    }

    /**
     * Export enquiries
     *
     * @queryParam charity string Filter by charity ref. No-example
     * @queryParam event string Filter by event ref. No-example
     * @queryParam year int Filter by year. No-example
     * @queryParam month int Filter by month. No-example
     * @queryParam status string Filter by status. Must be one of pending, processed. Example: pending
     * @queryParam partner string Filter by partner ref. No-example
     * @queryParam channel string Filter by partner channel ref. No-example
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
     * @param ExternalEnquiryListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(ExternalEnquiryListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->enquiryDataService->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while exporting events\' data.', 400);
        }
    }

    /**
     * Offer the event place to the enquirer.
     *
     * @param  ExternalEnquiryOfferPlaceRequest  $request
     * @param  string $ref
     * @return JsonResponse
     */
    public function offerPlace(ExternalEnquiryOfferPlaceRequest $request, string $ref): JsonResponse
    {
        try {
            $_enquiry = ExternalEnquiry::with(['event.eventCategories'])
                ->where('ref', $ref)
                ->filterByAccess()
                ->firstOrFail();

            if ($_enquiry->participant_id)
                throw new \Exception('The enquiry has already been offered a place and converted to a participant!');

            if (!($_enquiry->email && EmailAddressHelper::isValid($_enquiry->email)))
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
                    $request['email'] = $_enquiry->email;
                    $request['first_name'] = $_enquiry->first_name ?? "";
                    $request['last_name'] = $_enquiry->last_name ?? "";

                    DB::beginTransaction();

                    $register = Participant::registerForEvent($request, $eec, ParticipantAddedViaEnum::ExternalEnquiryOffer, null, true);

                    // Update the external enquiry
                    $_enquiry->participant_id = $register->participant->id;
                    $_enquiry->converted = true;
                    $timeline = $_enquiry->timeline;
                    $timeline[] = ['caption' => 'Converted', 'value' => $_enquiry->converted, 'datetime' => $_enquiry->updated_at];
                    $_enquiry->timeline = $timeline;
                    $_enquiry->save();

                    DB::commit();

                    if ($register->isDoubleRegistration) { // Create participantExtra when a double registration was made with some differences on profile information
                        if (ExternalEnquiryHelper::isProfileDifferentFromParentRecordProfile($register->participant, $_enquiry)) {
                            $participantExtra = ExternalEnquiryHelper::createParticipantExtraProfile($register->participant, $_enquiry, []);
                            Log::channel(clientSite()?->code . 'ldtoffer')->info('Participant_Extra Created: ' . json_encode(collect($participantExtra->toArray())->all()));
                        }
                    }

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

                    event(new ParticipantNewRegistrationsEvent($register->user, $extraData, $register->participant->invoiceItem?->invoice, clientSite(), $_enquiry, $participantExtra ?? null)); // Notify participant via email
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
