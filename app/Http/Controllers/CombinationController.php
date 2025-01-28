<?php

namespace App\Http\Controllers;

use App\Enums\ListTypeEnum;
use App\Enums\MetaRobotsEnum;
use App\Facades\ClientOptions;
use App\Http\Requests\ClientEventsSubListingQueryParamsRequest;
use App\Http\Requests\CombinationListingQueryParamsRequest;
use App\Http\Requests\CombinationPathRequest;
use App\Http\Requests\DeleteCombinationFaqDetailsRequest;
use App\Http\Requests\DeleteCombinationFaqsRequest;
use App\Http\Requests\DeleteCombinationsRequest;
use App\Http\Requests\RestoreCombinationsRequest;
use App\Http\Requests\StoreCombinationRequest;
use App\Http\Requests\UpdateCombinationRequest;
use App\Models\City;
use App\Models\Combination;
use App\Models\Faq;
use App\Models\FaqDetails;
use App\Models\Redirect;
use App\Models\Venue;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Event\Models\Serie;
use App\Repositories\FaqRepository;
use App\Services\Analytics\Events\AnalyticsViewEvent;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\CombinationDataService;
use App\Services\DataServices\EventClientDataService;
use App\Services\EventListingService;
use App\Services\DefaultQueryParamService;
use App\Services\FileManager\Traits\SingleUploadModel;
use App\Services\FileManager\Traits\UploadModelTrait;
use App\Services\RedirectManager;
use App\Traits\DraftCustomValidator;
use App\Traits\HelperTrait;
use App\Traits\Response;
use App\Traits\SiteTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CombinationController extends Controller
{
    use Response, /*SingleUploadModel,*/ SiteTrait,UploadModelTrait, HelperTrait, DraftCustomValidator;

    public function __construct(protected FaqRepository $faqRepository, protected CombinationDataService $combinationDataService)
    {
        parent::__construct();
    }

    /**
     * Get Combinations
     *
     * Get paginated list of combinations.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam drafted string Specifying how to interact with drafted items. Example: with
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam term string Specifying a keyword similar to combination name, meta title, meta description, event category name, region name/description, city name/description, venue name/description. Example: some combination name
     * @queryParam per_page string Overriding the default (10) number of listings per-page. Example: 20
     * @queryParam meta_keywords string Specifying comma seperated values matching items in combination's meta keywords attribute array. Example: keyword-1
     * @queryParam faqs string Specifying the inclusion of ONLY models with associated FAQs. Example: with
     * @queryParam period string Filter by specifying a period. Example: 1h,6h,12h,24h,7d,30d,90d,180d,1y,All
     * @queryParam year string Filter by specifying a year. Example: 2022
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,url:asc,created_at:desc
     * @queryParam category string Filter by event category, using a valid ref. No-example
     * @queryParam region string Filter by region, using a valid ref. No-example
     * @queryParam city string Filter by city, using a valid ref. No-example
     * @queryParam venue string Filter by venue, using a valid ref. No-example
     *
     * @param CombinationListingQueryParamsRequest $request
     * @return JsonResponse
     */
    public function index(CombinationListingQueryParamsRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $combinations = (new CacheDataManager(
                $this->combinationDataService->setLoadRedirect(true),
                'getPaginatedList',
                [$request]
            ))->getData();

             // Decode the JSON strings
             foreach ($combinations as &$combination) {
                $combination->event_category_id = json_decode($combination->event_category_id, true);
                $combination->region_id = json_decode($combination->region_id, true);
                $combination->city_id = json_decode($combination->city_id, true);
                $combination->venue_id = json_decode($combination->venue_id, true);
                $combination->series_id = json_decode($combination->series_id, true);
            }

            return $this->success('Combinations List', 200, [
                'combinations' => $combinations,
                'options' => [
                    ...ClientOptions::only('general', ['period', 'faqs', 'order_direction', 'deleted', 'drafted']),
                    ...ClientOptions::only('combinations', ['order_by', 'year']),
                ],
                'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Combinations))->getDefaultQueryParams()
            ]);
        } catch (NotFoundExceptionInterface $e) {
            Log::error($e);
            return $this->error('No result(s) found.', 404);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e);
            return $this->error('An error occurred while fetching Combinations.', 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Combinations.', 400);
        }
    }

    /**
     * Get Client Combinations
     *
     * Get paginated list of combinations on Client page.
     *
     * @group Combination
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @queryParam term string Specifying a keyword similar to combination name, meta title, meta description, event category name, region name/description, city name/description, venue name/description. Example: some combination name
     * @queryParam per_page string Overriding the default (10) number of listings per-page. Example: 20
     * @queryParam popular bool Filter by most popular. Example: true
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function _index(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'term' => ['sometimes', 'nullable', 'string'],
            'popular' => ['sometimes', 'nullable', 'string'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $combinations = (new CacheDataManager(
                $this->combinationDataService,
                '_index'
            ))->getData();

            return $this->success('Combinations List', 200, [
                'combinations' => $combinations
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error($e);
            return $this->error('No result(s) found.', 404);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e);
            return $this->error('An error occurred while fetching Combinations.', 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Combinations.', 400);
        }
    }

    /**
     * Create a new Combination
     *
     * A new combination can be created with optional FAQs properties for combinations that requires FAQs.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @param StoreCombinationRequest $request
     * @return JsonResponse
     */
    public function store(StoreCombinationRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $regions = $request->regions;
            $cities = $request->cities;
            $venues = $request->venues;
            $series = $request->series;
            $distances = $request->distances;
            $date = $request->date;
            $year = $request->year;
            $month = $request->month;
            $promote_flag = $request->promote_flag;
            $priority_number = $request->priority_number;

            $combination = Combination::create($validated = $request->validated());
            Combination::where('id', $combination->id)
             ->update([
                 'region_id' => $regions,
                 'city_id' => $cities,
                 'venue_id' => $venues,
                 'series_id' => $series,
                 'event_category_id' => $distances,
                 'date' => $date,
                 'year' => $year,
                 'month' => $month,
                 'promote_flag' => $promote_flag,
                 'priority_number' => $priority_number,
             ]);

            if (isset($validated['meta'])) {
                $combination->addMeta($validated['meta']);
            }

            if (isset($validated['faqs'])) {
                $faqs = $this->faqRepository->store($validated, $combination);
            }

            // if (isset($validated['cover_image'])) {
            //     $this->addFileToSingleUploadModel(
            //         $combination,
            //         $request,
            //         'cover_image',
            //         'image',
            //     );
            // }

            if ($request->filled('image')) { // Save the combination's image
                $this->attachSingleUploadToModel($combination, $request->image);
            }

            if ($request->filled('gallery')) { // Save the combination's gallery
                $this->attachMultipleUploadsToModel($combination, $request->gallery);
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while creating combination.', 400, $exception->getMessage());
        }

        return $this->success('New combination created.', 201, [
            'combination' => $combination->fresh()
        ]);
    }

    /**
     * Fetch Combination Options
     *
     * Retrieve combination creation options data.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return $this->success('Combination options retrieved.', 200, [
            'options' => [
                'robots' => MetaRobotsEnum::_options()
            ]
        ]);
    }

    /**
     * Fetch Combination
     *
     * Retrieve combination data matching specified ref attribute.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam ref string required The ref attribute of the page. Example: 97f62d3d-bf9d-42ac-88c4-9d56cd910d7a
     *
     * @param string $combination
     * @return JsonResponse
     */
    public function show(string $combination): JsonResponse
    {
        try {
            $combination = (new CacheDataManager(
                $this->combinationDataService,
                'show',
                [$combination]
            ))->getData();

            // Decode the JSON strings
            $combination->event_category_id = json_decode($combination->event_category_id, true);
            $combination->region_id = json_decode($combination->region_id, true);
            $combination->city_id = json_decode($combination->city_id, true);
            $combination->venue_id = json_decode($combination->venue_id, true);
            $combination->series_id = json_decode($combination->series_id, true);

            return $this->success('Combination data retrieved.', 200, [
                'combination' => $combination,
                'robots' => MetaRobotsEnum::_options()
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->error('No result(s) found.', 404);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e);
            return $this->error('An error occurred while fetching Combinations.', 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Combinations.', 400);
        }
    }

    /**
     * Fetch Client Combination
     *
     * Retrieve combination data matching specified slug attribute on client page.
     *
     * @group Combination
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @queryParam name string Filter by name. The term to search for. No-example
     * @queryParam category string Filter by event category ref. Example: 97d417f7-082f-4ca8-bc4a-ba9b2cb3fc4d
     * @queryParam dates string[] Filter by a range of dates. Must be a valid date in the format d-m-Y. Example: ["22-02-2018", "22-10-2023"]
     * @queryParam price integer[] Filter by a price range. Example: [12, 80]
     * @queryParam region string Filter by region id. No-example
     * @queryParam address string Filter by address. No-example
     * @queryParam date string Filter by date. Must be one of newest, oldest, this_year, next_year, next_3_months, next_6_months, 2022-09, 2022-10. No-example
     * @queryParam per_page integer Items per page. Example: 10
     *
     * @urlParam slug string required The slug attribute of the page. Example: some-combination-name
     *
     * @param ClientEventsSubListingQueryParamsRequest $request
     * @param string $combination
     * @return JsonResponse
     */
    public function _show(ClientEventsSubListingQueryParamsRequest $request, string $combination): JsonResponse|RedirectResponse
    {
        try {
            $combo = (new CacheDataManager(
                $this->combinationDataService,
                '_show',
                [$combination]
            ))->getData();

            $events = (new CacheDataManager(
                new EventClientDataService(),
                'getFilteredCombinationEvents',
                [$combo, $request]
            ))->extraKey('events')->getData();

            AnalyticsViewEvent::dispatch($combo);

            return $this->success('Combination data retrieved.', 200, [
                'combination' => $combo,
                'events' => $events,
                'price_range' => EventEventCategory::priceRange($combo, $request),
            ]);
        } catch (ModelNotFoundException $e) {
            $origin = request()->headers->get('origin') ?? '';
            return (new RedirectManager(Combination::class, $combination, 'path', $origin))->redirect();
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Combinations.', 400);
        }
    }

    /**
     * Fetch Client Combination by path
     *
     * Retrieve combination data matching specified path attribute.
     *
     * @group Combination
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @bodyParam path string required The path attribute of the combination. Example: /some-region/some-city
     *
     * @param ClientEventsSubListingQueryParamsRequest $request
     * @param CombinationPathRequest $combinationPathRequest
     * @return JsonResponse
     */
    public function _showByPath(ClientEventsSubListingQueryParamsRequest $request, CombinationPathRequest $combinationPathRequest): JsonResponse
    {
        $validated = $combinationPathRequest->validated();

        try {
            $combination = (new CacheDataManager(
                $this->combinationDataService,
                '_showByPath',
                [$validated['path']]
            ))->getData();

            $events = (new CacheDataManager(
                new EventClientDataService(),
                'getFilteredCombinationEvents',
                [$combination, $request]
            ))->extraKey('events')->getData();

            AnalyticsViewEvent::dispatch($combination);

            return $this->success('Combination data retrieved.', 200, [
                'combination' => $combination,
                'events' => $events,
                'price_range' => EventEventCategory::priceRange($combination, $request),
            ]);
        } catch (ModelNotFoundException $e) {
            $origin = request()->headers->get('origin') ?? '';
            return (new RedirectManager(Combination::class, $validated['path'], 'path', $origin))->redirect();
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Combinations.', 400);
        }
    }

    /**
     * Fetch Client Combination by path - GET
     *
     * Retrieve combination data matching specified path attribute.
     *
     * @group Combination
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @urlParam path string required The path attribute of the combination. Example: /some-region/some-city
     *
     * @param ClientEventsSubListingQueryParamsRequest $request
     * @param string $path
     * @return JsonResponse
     */
    public function _showByPathGet(ClientEventsSubListingQueryParamsRequest $request, string $path): JsonResponse
    {
        if (! Str::startsWith($path, '/')) $path = "/$path";

        try {
            $combination = (new CacheDataManager(
                $this->combinationDataService,
                '_showByPath',
                [$path]
            ))->getData();

            $events = (new CacheDataManager(
                new EventClientDataService(),
                'getFilteredCombinationEvents',
                [$combination, $request]
            ))->extraKey('events')->getData();

            AnalyticsViewEvent::dispatch($combination);

            return $this->success('Combination data retrieved.', 200, [
                'combination' => $combination,
                'events' => $events,
                'price_range' => EventEventCategory::priceRange($combination, $request),
            ]);
        } catch (ModelNotFoundException $e) {
            $origin = request()->headers->get('origin') ?? '';
            return (new RedirectManager(Combination::class, $path, 'path', $origin))->redirect();
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Combinations.', 500);
        }
    }

    /**
     * Update a Combination
     *
     * An existing combination can be modified, alongside their FAQs properties when necessary.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam ref string required The ref attribute of the page. Example: 97f62d3d-bf9d-42ac-88c4-9d56cd910d7a
     *
     * @param UpdateCombinationRequest $request
     * @param Combination $combination
     * @return JsonResponse
     */
    public function update(UpdateCombinationRequest $request, Combination $combination): JsonResponse
    {
        try {
            $validated = $request->validated();

            $regions = $request->regions;
            $cities = $request->cities;
            $venues = $request->venues;
            $series = $request->series;
            $distances = $request->distances;
            $date = $request->date;
            $year = $request->year;
            $month = $request->month;
            $promote_flag = $request->promote_flag;
            $priority_number = $request->priority_number;

            $combination->update(
                array_merge(
                    array_filter(['name' => $request['name'] ?? null]),
                    [
                        'description' => $request['description'] ?? null,
                        'date' => $date ?? null,
                        'year' => $year ?? null,
                        'promote_flag' => $promote_flag ?? 0,
                        'priority_number' => $priority_number ?? 0,
                        'month' => $month ?? null,
                        'event_category_id' => $distances ?? null,
                        'region_id' => $regions ?? null,
                        'city_id' => $cities ?? null,
                        'venue_id' => $venues ?? null,
                        'series_id' => $series ?? null,
                        'path' => $request['path'] ?? null,
                    ]
                )
            );

            // $combination->update(
            //     array_merge(
            //         array_filter(['name' => $validated['name'] ?? null]),
            //         [
            //             'description' => $validated['description'] ?? null,
            //             'event_category_id' => $validated['event_category_id'] ?? null,
            //             'region_id' => $validated['region_id'] ?? null,
            //             'city_id' => $validated['city_id'] ?? null,
            //             'venue_id' => $validated['venue_id'] ?? null,
            //             'path' => $validated['path'] ?? null,
            //         ]
            //     )
            // );

            if (isset($validated['meta'])) {
                $combination = $combination->addMeta($validated['meta']);
            }

            if (isset($validated['faqs'])) {
                $faqs = $this->faqRepository->update($validated, $combination);
            }

            // if (isset($validated['cover_image'])) {
            //     $this->addFileToSingleUploadModel(
            //         $combination,
            //         $request,
            //         'cover_image',
            //         'image',
            //     );
            // }

            if ($request->filled('image')) { // Save the combination's image
                $this->attachSingleUploadToModel($combination, $request->image);
            }

            if ($request->filled('gallery')) { // Save the combination's gallery
                $this->attachMultipleUploadsToModel($combination, $request->gallery);
            }

            return $this->success('Combination has been updated.', 201, [
                'combination' => $combination->load([
                    'faqs',
                    'meta',
                    'image',
                    'gallery'
                ])
            ]);

        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while updating combination.', 400);
        }
    }

    /**
     * Mark as published one or many combinations
     *
     * Publish multiple combinations data by specifying their ids.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required The list of ids associated with combinations. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsPublished(Request $request)
    {
        $validator = Validator::make($request->all(), $this->markAsPublishedValidationRules('combinations'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            Combination::whereIntegerInRaw('id', $request->ids)->onlyDrafted()->markAsPublished();

            CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());

            return $this->success('Successfully marked as draft the combination(s)', 200);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while publishing combination.', 400);
        }
    }

    /**
     * Mark as draft one or many combinations
     *
     * Draft multiple combinations data by specifying their ids.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required The list of ids associated with combinations. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsDraft(Request $request)
    {
        $validator = Validator::make($request->all(), $this->markAsDraftValidationRules('combinations'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            Combination::whereIntegerInRaw('id', $request->ids)->markAsDraft();

            CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());

            return $this->success('Successfully marked as draft the combination(s)', 200);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while publishing combination.', 400);
        }
    }

    /**
     * Delete Many Combinations
     *
     * Delete multiple combinations data by specifying their ids.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam combinations_ids string[] required The list of ids associated with combinations. Example: [1,2]
     * @queryParam permanently string Optionally specifying to force-delete model, instead of the default soft-delete. Example: 1
     *
     * @param DeleteCombinationsRequest $request
     * @return JsonResponse
     */
    public function destroyMany(DeleteCombinationsRequest $request): JsonResponse
    {
        try {
            $force = request('permanently') == 1;

            Combination::findMany($request->validated('combinations_ids'))->each(function ($combination) use ($force) {
                if ($force) {
                    $combination->forceDelete();
                } else $combination->delete();
            });

//            $query = Combination::query()
//                ->whereIntegerInRaw('id', $request->validated('combinations_ids'))
//                ->withDrafted();
//
//            if ($force = (request('permanently') == 1)) {
//                $query->forceDelete();
//            } else $query->delete();

//            CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());

            return $this->success('Combination(s) has been ' . ($force ? 'permanently ' : null) . 'deleted.', 200, [
                'combinations' => Combination::latest()->paginate(10),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while deleting specified combination(s).', 400);
        }
    }

    /**
     * Restore Many Combinations
     *
     * Restore multiple combinations data by specifying their ids.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam combinations_ids string[] required The list of ids associated with combinations. Example: [1,2]
     *
     * @param RestoreCombinationsRequest $request
     * @return JsonResponse
     */
    public function restoreMany(RestoreCombinationsRequest $request): JsonResponse
    {
        try {
            Combination::onlyTrashed()
                ->whereIn('id', $request->validated('combinations_ids'))
                ->withDrafted()
                ->restore();

            CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());

            return $this->success('Specified combination(s) has been restored.', 200, [
                'combinations' => Combination::latest()->paginate(10)
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while restoring specified combination(s).', 400);
        }
    }

    /**
     * Delete One/Many FAQs
     *
     * Delete multiple Combination FAQs by specifying their ids.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam faqs_ids string[] required The list of ids associated with specific combination FAQs ids. Example: [1,2]
     *
     * @param DeleteCombinationFaqsRequest $request
     * @param Combination $combination
     * @return JsonResponse
     */
    public function destroyManyFaqs(DeleteCombinationFaqsRequest $request, Combination $combination): JsonResponse
    {
        try {
            $combination->faqs()
                ->whereIn('id', $request->validated('faqs_ids'))
                ->delete();

            CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());

            return $this->success('Combination FAQ(s) has been deleted.', 200, [
                'combination' => $combination->fresh()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while deleting specified Combination FAQ(s).', 400);
        }
    }

    /**
     * Delete One/Many FAQ Details
     *
     * Delete multiple Combination FAQ details by specifying their ids.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam faq_details_ids string[] required The list of ids associated with specific page faq_details ids. Example: [1,2]
     *
     * @param DeleteCombinationFaqDetailsRequest $request
     * @param Combination $combination
     * @param Faq $faq
     * @return JsonResponse
     */
    public function destroyManyFaqDetails(DeleteCombinationFaqDetailsRequest $request, Combination $combination, Faq $faq): JsonResponse
    {
        try {
            $faq->faqDetails()
                ->whereIn('id', $request->validated('faq_details_ids'))
                ->delete();

            CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());

            return $this->success('Combination FAQ detail(s) has been deleted.', 200, [
                'combination' => $combination->fresh()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while deleting specified Combination FAQ details.', 400);
        }
    }

    /**
     * Remove faq details image
     *
     * @param  Combination $combination
     * @param  Faq $faq
     * @param  FaqDetails $faqDetails
     * @param  string $upload_ref
     * @return JsonResponse
     */
    public function removeFaqDetailImage(Combination $combination, Faq $faq, FaqDetails $faqDetails, string $upload_ref)
    {
        try {
            $this->faqRepository->removeImage($faqDetails, $upload_ref);

            CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());

            return $this->success('Successfully removed the image!', 200, [
                'combination' =>  $combination->load(['faqs'])
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->error('The image was not found!', 404);
        }
    }

    /**
     * Delete Meta
     *
     * Delete Combination Meta.
     *
     * @group Combination
     * @authenticated
     * @header Content-Type application/json
     *
     * @param Combination $combination
     * @return JsonResponse
     */
    public function destroyMeta(Combination $combination): JsonResponse
    {
        try {
            $combination = $combination->deleteMeta();

            CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());

            return $this->success('Combination Meta has been deleted.', 200, [
                'combination' => $combination
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while deleting Combination Metadata.', 400);
        }
    }

    public function getCitiesForCombination(Request $request): JsonResponse
    {
        try {
            $term = $request->input('term');
            $limit = 100;
            $query = City::where('site_id', clientSiteId());
            if ($term) {
                $query->where('name', 'LIKE', "%{$term}%");
            }
            $cities = $query->orderBy('id', 'DESC')->limit($limit)->get();
            return response()->json([
                'status' => 'success',
                'message' => 'Cities fetched successfully',
                'data' => $cities
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch cities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getVanuesForCombination(Request $request): JsonResponse
    {
        try {
            $term = $request->input('term');
            $limit = 100;
            $query = Venue::where('site_id', clientSiteId());
            if ($term) {
                $query->where('name', 'LIKE', "%{$term}%");
            }
            $vanues = $query->orderBy('id', 'DESC')->limit($limit)->get();
            return response()->json([
                'status' => 'success',
                'message' => 'Venue fetched successfully',
                'data' => $vanues
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch Venue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSeriesForCombination(Request $request): JsonResponse
    {
        try {
            $term = $request->input('term');
            $limit = 100;
            $query = Serie::where('site_id', clientSiteId());
            if ($term) {
                $query->where('name', 'LIKE', "%{$term}%");
            }
            $series = $query->orderBy('id', 'DESC')->limit($limit)->get();
            return response()->json([
                'status' => 'success',
                'message' => 'Series fetched successfully',
                'data' => $series
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch Series',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getYearsForCombination(Request $request): JsonResponse
    {
        try {
            $currentYear = Carbon::now()->year;
            $years = [];

            for ($i = 0; $i <= 5; $i++) {
                $years[] = $currentYear + $i;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Years fetched successfully',
                'data' => $years
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch years',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMonthForCombination(Request $request): JsonResponse
    {
        try {
            $months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Months fetched successfully',
                'data' => $months
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch months',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
