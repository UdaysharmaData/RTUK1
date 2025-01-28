<?php

namespace App\Http\Controllers;

use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\VenueDataService;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\RedirectManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\Models\Faq;
use App\Models\Venue;
use App\Models\Upload;
use App\Models\FaqDetails;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;
use App\Facades\ClientOptions;
use App\Http\Helpers\AccountType;
use App\Repositories\FaqRepository;
use App\Http\Requests\StoreVenueRequest;
use App\Http\Requests\UpdateVenueRequest;
use App\Services\DefaultQueryParamService;
use App\Http\Requests\VenuesDeleteRequest;
use App\Http\Requests\VenuesRestoreRequest;
use App\Http\Requests\DeleteVenueFaqsRequest;
use App\Http\Requests\DeleteFaqDetailsRequest;
use App\Modules\Event\Resources\EventResource;
use App\Http\Requests\VenueAllQueryParamsRequest;
use App\Http\Requests\VenueListingQueryParamsRequest;

use App\Enums\ListTypeEnum;
use App\Enums\MetaRobotsEnum;
use App\Enums\OrderByDirectionEnum;
use App\Enums\EventPropertyServicesListOrderByFieldsEnum;

use App\Modules\Event\Models\EventEventCategory;
use App\Services\DataServices\EventClientDataService;
use App\Traits\DraftCustomValidator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Venues
 * Manages venues on the application
 * @authenticated
 */

class VenueController extends Controller
{
    use Response, UploadTrait, SiteTrait, DraftCustomValidator;

    public function __construct(protected FaqRepository $faqRepository, protected VenueDataService $venueDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_venues', [
            'except' => [
                'all',
                '_index',
                'events'
            ]
        ]);
    }

    /**
     * Paginated venues for dropdown fields.
     *
     * @group Venues
     *
     * @queryParam country string Filter by country ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam region string Filter by region ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam city string Filter by city ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param VenueAllQueryParamsRequest $request
     */
    public function all(VenueAllQueryParamsRequest $request): JsonResponse
    {
        try {
            $venues = (new CacheDataManager(
                $this->venueDataService->setRelations(['city:id,ref,name,slug,region_id', 'city.region:id,ref,name,slug,country']),
                'all',
                [$request]
            ))->getData();

            return $this->success('All venues', 200, [
                'venues' => $venues
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting venue list.', 404);
        }
    }

    /**
     * The list of venues
     *
     * @group Venues
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam country string Filter by country ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam region string Filter by region ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam city string Filter by city ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam faqs string Specifying the inclusion of ONLY pages with associated FAQs. Should be one of with, without. Example: with
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     * @qqueryParam drafted string Specifying how to interact with drafted items. Example: with
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     *
     * @param  VenueListingQueryParamsRequest  $request
     * @return JsonResponse
     */
    public function index(VenueListingQueryParamsRequest $request): JsonResponse
    {
        try {
            $venues = (new CacheDataManager(
                $this->venueDataService->setRelations(['image', 'redirect']),
                'getPaginatedList',
                [$request]
            ))->getData();

            return $this->success('The list of venues', 200, [
                'venues' => $venues,
                'options' => ClientOptions::only('venues', [
                    'faqs',
                    'deleted',
                    'drafted',
                    'order_by',
                    'order_direction'
                ]),
                'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::EventPropertyServices))
                    ->setParams(['order_by' => EventPropertyServicesListOrderByFieldsEnum::Name->value . ":" . OrderByDirectionEnum::Ascending->value])
                    ->getDefaultQueryParams(),
                'action_messages' => Venue::$actionMessages
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting venue list.', 404);
        }
    }

    /**
     * The list of venues
     *
     * @group Venues - Client
     * @unauthenticated
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam country string Filter by country ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam region string Filter by region ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam city string Filter by city ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam popular bool Filter by most popular. Example: true
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function _index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'term' => ['sometimes', 'nullable', 'string'],
            'country' => ['sometimes', 'nullable', 'string'],
            'region' => ['sometimes', 'nullable', 'exists:regions,ref'],
            'city' => ['sometimes', 'nullable', 'exists:cities,ref'],
            'popular' => ['sometimes', 'nullable', 'boolean'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $venues = (new CacheDataManager(
                $this->venueDataService->setRelations(['image']),
                '_index',
                [$request]
            ))->getData();

            return $this->success('The list of venues', 200, [
                'venues' => $venues
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting venue list.', 404);
        }
    }

    /**
     * Get the events under a venue
     *
     * @group Venues - Client
     * @unauthenticated
     *
     * @queryParam name string Filter by name. The term to search for. No-example
     * @queryParam category string Filter by event category ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam start_date string Filter by start_date. Must be a valid date in the format d-m-Y. Example: "22-02-2018"
     * @queryParam end_date string Filter by end_date. Must be a valid date in the format d-m-Y. Example: "22-02-2023"
     * @queryParam price integer[] Filter by a price range. Example: [12, 80]
     * @queryParam region string Filter by region ref. No-example
     * @queryParam address string Filter by address. No-example
     * @queryParam virtual_events string Filter by virtual_events. Must be one of include, exclude, only. Example: include
     * @queryParam date string Filter by date. Must be one of newest, oldest, this_year, next_year, next_3_months, next_6_months, 2022-09, 2022-10. No-example
     * @queryParam skip integer The number of items to skip before taking the number of items specified by the take query param Example: 6
     * @queryParam take integer Number of items to return. Example 6
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @urlParam venue_slug string required The slug of the venue. Example: midlands
     *
     * @param  Request        $request
     * @param  string         $slug
     * @return JsonResponse
     */
    public function events(Request $request, $slug): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'nullable', Rule::exists('event_categories', 'ref')],
            'start_date' => ['sometimes', 'nullable', 'date_format:d-m-Y'],
            'end_date' => ['sometimes', 'nullable', 'date_format:d-m-Y', 'after_or_equal:start_date'],
            'price' => ['sometimes', 'nullable', 'array', 'size:2'],
            'price.*' => ['integer'],
            'region' => ['sometimes', 'nullable', 'string', Rule::exists('regions', 'ref')->where(
                function ($query) {
                    return $query->where("site_id", static::getSite()?->id);
                }
            )],
            'address' => ['sometimes', 'nullable', 'string'],
            'virtual_events' => ['sometimes', 'nullable', 'in:include,exclude,only'],
            'date' => ['nullable', 'string'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $venue = (new CacheDataManager(
                $this->venueDataService,
                '_show',
                [$slug]
            ))->getData();

            $events = (new CacheDataManager(
                new EventClientDataService(),
                'getFilteredEventsByProperty',
                [$venue, $request]
            ))->extraKey('events')->getData();

            return $this->success('The venue details', 200, [
                'venue' => $venue,
                'events' => new EventResource($events),
                'price_range' => EventEventCategory::priceRange($venue, $request)
            ]);
        } catch (ModelNotFoundException $e) {
            $origin = request()->headers->get('origin') ?? '';
            return (new RedirectManager(Venue::class, $slug, 'slug', $origin))->redirect();
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error('An error occurred while getting venue details.', 404);
        }
    }

    /**
     * Create a venue
     * @group Venues
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        try {
            $sites = $this->venueDataService->sites();

            return $this->success('Create a venue', 200, [
                'sites' => $sites,
                'robots' => MetaRobotsEnum::_options()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting sites list.', 404);
        }
    }

    /**
     * Store a venue
     * @group Venues
     * @param  StoreVenueRequest $request
     * @return JsonResponse
     */
    public function store(StoreVenueRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $venue = $this->venueDataService->store($request);

            DB::commit();

            return $this->success('Successfully created the venue!', 200, [
                'venue' => $venue
            ]);
        } catch (QueryException | FileException | \Exception $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('Unable to create the venue! Please try again', 406, $e->getMessage());
        }
    }

    /**
     * Edit a venue
     * @group Venues
     * @urlParam venue_ref string required The ref of the venue. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param string $ref
     * @return JsonResponse
     */
    public function edit(string $ref): JsonResponse
    {
        try {
            $venue = (new CacheDataManager(
                $this->venueDataService,
                'edit',
                [$ref]
            ))->getData();

            $sites = $this->venueDataService->sites();

            return $this->success('Edit the venue', 200, [
                'venue' => $venue,
                'sites' => $sites,
                'action_messages' => Venue::$actionMessages,
                'robots' => MetaRobotsEnum::_options()
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->error('The venue was not found!', 404);
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error('An error occurred while getting venue details.', 404);
        }
    }

    /**
     * Update a venue
     * @group Venues
     * @urlParam venue_ref string required The ref of the venue. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  UpdateVenueRequest  $request
     * @param  Venue               $venue
     * @return JsonResponse
     */
    public function update(UpdateVenueRequest $request, Venue $venue): JsonResponse
    {
        try {
            DB::beginTransaction();

            $_venue = $this->venueDataService->update($request, $venue->ref);

            DB::commit();

            return $this->success('Successfully updated the venue!', 200, [
                'venue' => $_venue
            ]);
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to update the venue! Please try again.', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return $this->error('The venue was not found!', 404);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('An error occurred while updating venue', 404);
        }
    }

    /**
     * Get a venue's details.
     * @group Venues
     * @urlParam venue_ref string required The ref of the venue. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string $ref
     * @return JsonResponse
     */
    public function show(string $ref): JsonResponse
    {
        try {
            $_venue = (new CacheDataManager(
                $this->venueDataService,
                'show',
                [$ref]
            ))->getData();

            return $this->success('The venue details', 200, [
                'venue' => $_venue
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->error('The venue was not found!', 404);
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error('An error occurred while fetching venue details.', 404);
        }
    }

    /**
     * Mark as published one or many venues
     *
     * @group Venues
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with venues. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsPublished(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsPublishedValidationRules('venues'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();

            $this->venueDataService->markAsPublished($request->ids);

            DB::commit();

            return $this->success('Successfully marked as published the venue(s)!', 200);
        } catch (\Exception $e) {
            DB::rollback();

            return $this->error('Unable to mark as published the venue(s)! Please try again.', 406, $e->getMessage());
        }
    }

    /**
     * Mark as draft one or many venues
     *
     * @group Venues
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with venues. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsDraft(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsDraftValidationRules('venues'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();

            $this->venueDataService->markAsDraft($request->ids);

            DB::commit();

            return $this->success('Successfully marked as draft the venue(s)!', 200);
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to mark as draft the venue(s)! Please try again.', 406, $e->getMessage());
        }
    }

    /**
     * Delete one or many venues
     *
     * @group Venues
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with venues. Example: [1,2]
     *
     * @param VenuesDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(VenuesDeleteRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $this->venueDataService->destroy($request->validated('ids'));

            DB::commit();

            return $this->success('Successfully deleted the venue(s)', 200);
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to delete the venue! Please try again.', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return $this->error('The venue was not found!', 404);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('An error occurred while deleting venue', 404);
        }
    }

    /**
     * Restore one or many venues
     *
     * @group Venues
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with venues. Example: [1,2]
     *
     * @param  VenuesRestoreRequest $request
     * @return JsonResponse
     */
    public function restore(VenuesRestoreRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $this->venueDataService->restore($request->validated('ids'));

            DB::commit();

            return $this->success('Successfully restored the venue(s)', 200);
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to restore the venue! Please try again.', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return $this->error('The venue was not found!', 404);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('An error occurred while restoring venue', 404);
        }
    }



    /**
     * Delete one or many venues (Permanently)
     * Only the administrator can delete a venue permanently.
     *
     * @bodyParam ids string[] required An array list of ids associated with venues. Example: [1,2]
     *
     * @param  VenuesDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(VenuesDeleteRequest $request): JsonResponse
    {
        if (!AccountType::isAdmin()) { // Only the administrator can delete an event permanently.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        try {
            DB::beginTransaction();

            $this->venueDataService->destroyPermanently($request->validated('ids'));

            DB::commit();


            return $this->success('Successfully deleted the venue(s)', 200);
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to delete the venue permanently! Please try again.', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return $this->error('The venue was not found!', 404);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('An error occurred while deleting venue permanently', 404);
        }
    }

    /**
     * Delete the venue's image
     * @group Venues
     * @urlParam venue_ref string required The ref of the venue. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam upload_ref string required The ref of the upload. Example: 97ad9df6-d927-4a44-8fec-3daacee89678
     *
     * @param Venue $venue
     * @param Upload $upload
     * @return JsonResponse
     */
    public function removeImage(Venue $venue, Upload $upload): JsonResponse
    {
        try {
            $_venue = $this->venueDataService->removeImage($venue, $upload);

            CacheDataManager::flushAllCachedServiceListings($this->venueDataService);

            return $this->success('Successfully deleted the image!', 200, [
                'venue' => $_venue->load(['site', 'image', 'gallery', 'faqs'])
            ]);
        } catch (QueryException | \Exception $e) {
            Log::error($e);

            return $this->error('Unable to delete the image! Please try again', 406);
        }
    }

    /**
     * Export venues
     * @group Venues
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam faqs string Specifying the inclusion of ONLY pages with associated FAQs. Should be one of with, without. Example: with
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param VenueListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(VenueListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->venueDataService->setRelations(['meta'])->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error("An error occurred while exporting venues data", 404);
        }
    }

    /**
     * Delete One/Many Faqs
     *
     * Delete multiple venue FAQS by specifying their ids
     *
     * @urlParam venue_ref string required The ref of the venue. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @bodyParam faqs_ids string[] required The list of ids associated with specific venue FAQs ids. Example: [1,2]
     *
     * @param  DeleteVenueFaqsRequest $request
     * @param  Venue $venue
     * @return JsonResponse
     */
    public function destroyManyFaqs(DeleteVenueFaqsRequest $request, Venue $venue): JsonResponse
    {
        try {
            $this->faqRepository->destroyManyFaqs($request->validated(), $venue);

            CacheDataManager::flushAllCachedServiceListings($this->venueDataService);

            return $this->success('Venue FAQ(s) has been deleted.', 200, [
                'venue' => $venue->load(['faqs'])
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while deleting specified venue FAQ(s).', 400);
        }
    }

    /**
     * Delete One/Many FAQ Details
     *
     * Delete multiple Venue FAQ details by specifying their ids.
     *
     * @urlParam venue_ref string required The ref of the venue. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam faq_ref string required The ref of the faq. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @bodyParam faq_details_ids string[] required The list of ids associated with specific venue faq_details ids. Example: [1,2]
     *
     * @param  DeleteFaqDetailsRequest $request
     * @param  Faq $faq
     * @param  Venue $venue
     * @return JsonResponse
     */
    public function destroyManyFaqDetails(DeleteFaqDetailsRequest $request, Venue $venue, Faq $faq): JsonResponse
    {
        try {
            $this->faqRepository->destroyManyFaqDetails($request->validated(), $faq);

            CacheDataManager::flushAllCachedServiceListings($this->venueDataService);

            return $this->success('Venue FAQ details(s) has been deleted', 200, [
                'venue' => $venue->load(['faqs'])
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while deleting specified venue FAQ details.', 400);
        }
    }

    /**
     * Remove faq details image
     *
     * @param  Venue $venue
     * @param  Faq $faq
     * @param  FaqDetails $faqDetails
     * @param  string $upload_ref
     * @return JsonResponse
     */
    public function removeFaqDetailImage(Venue $venue, Faq $faq, FaqDetails $faqDetails, string $upload_ref): JsonResponse
    {
        try {
            $this->faqRepository->removeImage($faqDetails, $upload_ref);

            CacheDataManager::flushAllCachedServiceListings($this->venueDataService);

            return $this->success('Successfully removed the image!', 200, [
                'venue' =>  $venue->load(['faqs'])
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->error('The image was not found!', 404);
        } catch (QueryException | \Exception $e) {
            Log::error($e);

            return $this->error('An error occurred while deleting image.', 404);
        }
    }
}
