<?php

namespace App\Http\Controllers;


use App\Services\RedirectManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Helpers\AccountType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\Models\City;
use App\Models\Upload;
use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;
use App\Repositories\FaqRepository;

use App\Http\Requests\CitiesDeleteRequest;
use App\Http\Requests\DeleteCityFaqsRequest;
use App\Http\Requests\DeleteFaqDetailsRequest;
use App\Http\Requests\CityListingQueryParamsRequest;

use App\Models\Faq;
use App\Enums\ListTypeEnum;
use App\Enums\OrderByDirectionEnum;
use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Enums\ListingFaqsFilterOptionsEnum;
use App\Services\DataServices\CityDataService;
use App\Services\DataCaching\CacheDataManager;
use App\Modules\Event\Resources\EventResource;
use App\Enums\EventPropertyServicesListOrderByFieldsEnum;
use App\Enums\MetaRobotsEnum;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;

use App\Models\FaqDetails;
use App\Facades\ClientOptions;
use App\Services\DefaultQueryParamService;
use App\Http\Requests\CitiesRestoreRequest;
use App\Http\Requests\CityAllQueryParamsRequest;
use App\Modules\Event\Models\EventEventCategory;
use App\Services\DataServices\EventClientDataService;
use App\Traits\DraftCustomValidator;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Cities
 * Manages cities on the application
 * @authenticated
 */
class CityController extends Controller
{
    use Response, UploadTrait, SiteTrait, DraftCustomValidator;

    public function __construct(protected FaqRepository $faqRepository, protected CityDataService $cityDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_cities', [
            'except' => [
                'all',
                '_index',
                'events'
            ]
        ]);
    }

    /**
     * Paginated cities for dropdown fields.
     * @group Cities
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam country string Filter by country ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam region string Filter by region ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param CityAllQueryParamsRequest $request
     */
    public function all(CityAllQueryParamsRequest $request): JsonResponse
    {
        try {
            $cities = (new CacheDataManager(
                $this->cityDataService->setRelations(['region:id,ref,name,slug,country']),
                'all',
                [$request]
            ))->getData();

            return $this->success('All cities', 200, [
                'cities' => $cities
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting cities list.', 404);
        }
    }

    /**
     * The list of cities
     * @group Cities
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam country string Filter by country ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam region string Filter by region ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam faqs string Specifying the inclusion of ONLY pages with associated FAQs. Should be one of with, without. Example: with
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     * @queryParam drafted string Specifying how to interact with drafted items. Example: with
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     *
     * @param  CityListingQueryParamsRequest  $request
     * @return JsonResponse
     */
    public function index(CityListingQueryParamsRequest $request): JsonResponse
    {
        try {
            $cities = (new CacheDataManager(
                $this->cityDataService->setRelations(['image', 'redirect']),
                'getPaginatedList',
                [$request]
            ))->getData();

            return $this->success('The list of cities', 200, [
                'cities' => $cities,
                'options' => ClientOptions::only('cities', [
                    'faqs',
                    'drafted',
                    'deleted',
                    'order_by',
                    'order_direction'
                ]),
                'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::EventPropertyServices))
                    ->setParams(['order_by' => EventPropertyServicesListOrderByFieldsEnum::Name->value . ":" . OrderByDirectionEnum::Ascending->value])
                    ->getDefaultQueryParams(),
                'action_messages' => City::$actionMessages
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting cities list.', 404);
        }
    }

    /**
     * The list of cities
     *
     * @group Cities - Client
     * @unauthenticated
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam country string Filter by country ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam region string Filter by region ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
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
            'popular' => ['sometimes', 'nullable', 'boolean'],
            'country' => ['sometimes', 'nullable', 'string'],
            'region' => ['sometimes', 'nullable', 'exists:regions,ref'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $cities = (new CacheDataManager(
                $this->cityDataService->setRelations(['image']),
                '_index',
                [$request]
            ))->getData();

            return $this->success('The list of cities', 200, [
                'cities' => $cities
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting cities list.', 404);
        }
    }

    /**
     * Get the events under a city
     *
     * @group Cities - Client
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
     * @urlParam city_slug string required The slug of the city. Example: midlands
     *
     * @param  Request        $request
     * @param  string         $slug
     * @return JsonResponse
     */
    public function events(Request $request, string $slug): JsonResponse
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
            'faqs' => ['sometimes', new Enum(ListingFaqsFilterOptionsEnum::class)],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $city = (new CacheDataManager(
                $this->cityDataService,
                '_show',
                [$slug]
            ))->getData();

            $events = (new CacheDataManager(
                new EventClientDataService(),
                'getFilteredEventsByProperty',
                [$city, $request]
            ))->extraKey('events')
            ->getData();

            return $this->success('The city details', 200, [
                'city' => $city,
                'events' => $events,
                'price_range' => EventEventCategory::priceRange($city, $request)
            ]);
        } catch (ModelNotFoundException $e) {
            $origin = request()->headers->get('origin') ?? '';
            return (new RedirectManager(City::class, $slug,'slug', $origin))->redirect();
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error('An error occurred while getting city details.', 404);
        }
    }

    /**
     * Create a city
     * @group Cities
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        try {
            $sites = $this->cityDataService->sites();

            return $this->success('Create a city', 200, [
                'sites' => $sites,
                'robots' => MetaRobotsEnum::_options()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting sites list.', 404);
        }
    }

    /**
     * Store a city
     * @group Cities
     * @param  StorecityRequest $request
     * @return JsonResponse
     */
    public function store(StoreCityRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $city = $this->cityDataService->store($request);

            DB::commit();

            return $this->success('Successfully created the city!', 200, [
                'city' => $city
            ]);
        } catch (QueryException | FileException | \Exception $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('Unable to create the city! Please try again', 406, $e->getMessage());
        }
    }

    /**
     * Edit a city
     * @group Cities
     * @urlParam city_ref string required The ref of the city. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param string $ref
     * @return JsonResponse
     */
    public function edit(string $ref): JsonResponse
    {
        try {
            $city = (new CacheDataManager(
                $this->cityDataService,
                'edit',
                [$ref]
            ))->getData();

            $sites = $this->cityDataService->sites();

            return $this->success('Edit the city', 200, [
                'city' => $city,
                'sites' => $sites,
                'action_messages' => City::$actionMessages,
                'robots' => MetaRobotsEnum::_options()
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->error('The city was not found!', 404);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting city details.', 404);
        }
    }

    /**
     * Update a city
     * @group Cities
     * @urlParam city_ref string required The ref of the city. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  UpdateCityRequest  $request
     * @param  City               $city
     * @return JsonResponse
     */
    public function update(UpdateCityRequest $request, City $city): JsonResponse
    {
        try {
            DB::beginTransaction();

            $_city = $this->cityDataService->update($request, $city->ref);

            DB::commit();

            return $this->success('Successfully updated the city!', 200, [
                'city' => $_city
            ]);
        } catch (QueryException $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('Unable to update the city! Please try again.', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return $this->error('The city was not found!', 404);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('An error occurred while updating city', 404);
        }
    }

    /**
     * Get a city's details.
     * @group Cities
     * @urlParam city_ref string required The ref of the city. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string $ref
     * @return JsonResponse
     */
    public function show(string $ref): JsonResponse
    {
        try {
            $_city = (new CacheDataManager(
                $this->cityDataService,
                'show',
                [$ref]
            ))->getData();

            return $this->success('The city details', 200, [
                'city' => $_city
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->error('The city was not found!', 404);
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error('An error occurred while fetching city details.', 404);
        }
    }

     /**
     * Mark as published one or many cities
     *
     * @group Cities
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with cities. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsPublished(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsPublishedValidationRules('cities'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();

            $this->cityDataService->markAsPublished($request->ids);

            DB::commit();

            return $this->success('Successfully marked as published the city(s)!', 200);
        } catch (\Exception $e) {
            DB::rollback();

            return $this->error('Unable to mark as published the city(s)! Please try again.', 406, $e->getMessage());
        }
    }

    /**
     * Mark as draft one or many cities
     *
     * @group Cities
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with cities. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsDraft(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsDraftValidationRules('cities'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();
            $this->cityDataService->markAsDraft($request->ids);

            DB::commit();

            return $this->success('Successfully marked as draft the city(s)!', 200);
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to mark as draft the city(s)! Please try again.', 406, $e->getMessage());
        }
    }

    /**
     * Delete one or many cities
     *
     * @group Cities
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with cities. Example: [1,2]
     *
     * @param CitiesDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(CitiesDeleteRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $this->cityDataService->destroy($request->validated('ids'));

            DB::commit();

            return $this->success('Successfully deleted the city(s)', 200);
        } catch (QueryException $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('Unable to delete the city! Please try again.', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return $this->error('The city was not found!', 404);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('An error occurred while deleting city', 404);
        }
    }


    /**
     * Restore one or many cities
     *
     * @group Cities
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with cities. Example: [1,2]
     *
     * @param  CitiesRestoreRequest $request
     * @return JsonResponse
     */
    public function restore(CitiesRestoreRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $this->cityDataService->restore($request->validated('ids'));

            DB::commit();

            return $this->success('Successfully restored the city(s)', 200);
        } catch (QueryException $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('Unable to restore the city! Please try again.', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return $this->error('The city was not found!', 404);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('An error occurred while restoring city', 404);
        }
    }

    /**
     * Delete one or many cities (Permanently)
     * Only the administrator can delete a city permanently.
     *
     * @bodyParam ids string[] required An array list of ids associated with cities. Example: [1,2]
     *
     * @param  CitiesDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(CitiesDeleteRequest $request): JsonResponse
    {
        if (!AccountType::isAdmin()) { // Only the administrator can delete an event permanently.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        try {
            DB::beginTransaction();

            $this->cityDataService->destroyPermanently($request->validated('ids'));

            DB::commit();

            return $this->success('Successfully deleted the city(s)', 200);
        } catch (QueryException $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('Unable to delete the city permanently! Please try again.', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return $this->error('The city was not found!', 404);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return $this->error('An error occurred while deleting city permanently', 404);
        }
    }

    /**
     * Delete the city's image
     * @group Cities
     * @urlParam city_ref string required The ref of the city. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam upload_ref string required The ref of the upload. Example: 97ad9df6-d927-4a44-8fec-3daacee89678
     *
     * @param City $city
     * @param Upload $upload
     * @return JsonResponse
     */
    public function removeImage(City $city, Upload $upload): JsonResponse
    {
        try {
            $_city = $this->cityDataService->removeImage($city, $upload);

            CacheDataManager::flushAllCachedServiceListings($this->cityDataService);

            return $this->success('Successfully deleted the image!', 200, [
                'city' => $_city->load(['site', 'image', 'gallery'])
            ]);
        } catch (QueryException | \Exception $e) {
            Log::error($e);

            return $this->error('Unable to delete the image! Please try again', 406);
        }
    }

    /**
     * Export cities
     * @group Cities
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     *
     * @param CityListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(CityListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->cityDataService->setRelations(['meta'])->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error("An error occurred while exporting cities data", 404);
        }
    }

    /**
     * Delete One/Many FAQs
     *
     * Delete multiple City FAQs by specifying their ids.
     *
     * @urlParam city_ref string required The ref of the city. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @bodyParam faqs_ids string[] required The list of ids associated with specific city FAQs ids. Example: [1,2]
     *
     * @param DeleteCityFaqsRequest $request
     * @param City $city
     * @return JsonResponse
     */
    public function destroyManyFaqs(DeleteCityFaqsRequest $request, City $city): JsonResponse
    {
        try {
            $this->faqRepository->destroyManyFaqs($request->validated(), $city);

            CacheDataManager::flushAllCachedServiceListings($this->cityDataService);

            return $this->success('City FAQ(s) has been deleted.', 200, [
                'city' => $city->load(['faqs'])
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while deleting specified city FAQ(s). Please try again.', 400);
        }
    }

    /**
     * Delete One/Many FAQ Details
     *
     * Delete multiple City FAQ details by specifying their ids.
     *
     * @urlParam city_ref string required The ref of the city. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam faq_ref string required The ref of the faq. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @bodyParam faq_details_ids string[] required The list of ids associated with specific city faq_details ids. Example: [1,2]
     *
     * @param DeleteFaqDetailsRequest $request
     * @param Faq $faq
     * @param City $city
     * @return JsonResponse
     */
    public function destroyManyFaqDetails(DeleteFaqDetailsRequest $request, City $city, Faq $faq): JsonResponse
    {
        try {
            $this->faqRepository->destroyManyFaqDetails($request->validated(), $faq);

            CacheDataManager::flushAllCachedServiceListings($this->cityDataService);

            return $this->success('City FAQ detail(s) has been deleted.', 200, [
                'city' => $city->load(['faqs'])
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while deleting specified city FAQ details.', 400);
        }
    }

    /**
     * Remove faq details image
     *
     * @param  City $city
     * @param  Faq $faq
     * @param  FaqDetails $faqDetails
     * @param  string $upload_ref
     * @return JsonResponse
     */
    public function removeFaqDetailImage(City $city, Faq $faq, FaqDetails $faqDetails, string $upload_ref): JsonResponse
    {
        try {
            $this->faqRepository->removeImage($faqDetails, $upload_ref);

            CacheDataManager::flushAllCachedServiceListings($this->cityDataService);

            return $this->success('Successfully removed the image!', 200, [
                'city' =>  $city->load(['faqs'])
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->error('The image was not found!', 404);
        } catch (QueryException | \Exception $e) {
            Log::error($e);

            return $this->error('Unable to delete the image! Please try again', 406, $e->getMessage());
        }
    }
}
