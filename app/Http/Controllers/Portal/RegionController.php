<?php

namespace App\Http\Controllers\Portal;

use App\Enums\PredefinedPartnersEnum;
use App\Models\Redirect;
use App\Models\Upload;
use App\Modules\Event\Models\Event;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\RegionDataService;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\RedirectManager;
use Illuminate\Support\Facades\DB;
use Rule;
use Excel;
use Storage;
use Validator;
use Illuminate\Http\Request;
use App\Facades\ClientOptions;
use App\Http\Helpers\AccountType;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Database\QueryException;
use App\Services\DefaultQueryParamService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\Models\Region;

use App\Http\Resources\RegionResource;
use App\Modules\Event\Resources\EventResource;

use App\Http\Requests\RegionDeleteRequest;
use App\Http\Requests\RegionListingQueryParamsRequest;

use App\Enums\ListTypeEnum;
use App\Enums\MetaRobotsEnum;
use App\Enums\OrderByDirectionEnum;
use App\Enums\RegionsListOrderByFieldsEnum;
use App\Enums\ListingFaqsFilterOptionsEnum;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\HelperTrait;
use App\Traits\UploadTrait;
use App\Traits\DownloadTrait;
use App\Traits\SingularOrPluralTrait;

use App\Models\Faq;
use App\Repositories\FaqRepository;
use Illuminate\Support\Facades\Log;

use App\Http\Requests\StoreRegionRequest;
use App\Http\Requests\UpdateRegionRequest;
use App\Http\Requests\RegionRestoreRequest;
use App\Http\Requests\DeleteFaqDetailsRequest;
use App\Http\Requests\DeleteRegionFaqsRequest;
use App\Http\Requests\RegionAllQueryParamsRequest;
use App\Models\FaqDetails;
use App\Modules\Event\Models\EventEventCategory;
use App\Services\DataServices\EventClientDataService;
use App\Traits\DraftCustomValidator;
use App\Modules\Setting\Enums\SiteEnum;

/**
 * @group Regions
 * Manages regions on the application
 * @authenticated
 */
class RegionController extends Controller
{
    use Response, SiteTrait, HelperTrait, UploadTrait, DownloadTrait, SingularOrPluralTrait, DraftCustomValidator;

    /*
    |--------------------------------------------------------------------------
    | Region Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with regions. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected FaqRepository $faqRepository, protected RegionDataService $regionDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_regions', [
            'except' => [
                'all',
                '_index',
                'events'
            ]
        ]);
    }

    /**
     * Paginated regions for dropdown fields.
     * @group Regions
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam country string Filter by country. Example: United Kingdom
     * @queryParam faqs string Specifying the inclusion of ONLY pages with associated FAQs. Should be one of with, without. Example: with
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param RegionAllQueryParamsRequest $request
     */
    public function all(RegionAllQueryParamsRequest $request): JsonResponse
    {
        try {
            $regions = (new CacheDataManager(
                $this->regionDataService,
                'all',
                [$request]
            ))->getData();

            return $this->success('All regions', 200, [
                'regions' => $regions
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting regions list.', 404);
        }
    }

    /**
     * The list of regions
     * @group Regions
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam country string Filter by country. Example: United Kingdom
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     * @queryParam drafted string Specifying how to interact with drafted items. Example: with
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param  RegionListingQueryParamsRequest  $request
     * @return JsonResponse
     */
    public function index(RegionListingQueryParamsRequest $request): JsonResponse
    {
        try {
            $regions = (new CacheDataManager(
                $this->regionDataService->setRelations(['image', 'redirect']),
                'getPaginatedList',
                [$request]
            ))->getData();

            return $this->success('The list of regions', 200, [
                'regions' => $regions,
                'options' => ClientOptions::only('regions', [
                    'faqs',
                    'deleted',
                    'drafted',
                    'order_by',
                    'order_direction'
                ]),
                'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Regions))
                    ->setParams(['order_by' => RegionsListOrderByFieldsEnum::Name->value . ":" . OrderByDirectionEnum::Ascending->value])
                    ->getDefaultQueryParams(),
                'action_messages' => Region::$actionMessages
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting regions list.', 404);
        }
    }

    /**
     * The list of regions
     *
     * @group Regions - Client
     * @unauthenticated
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam country string Filter by country. Example: United Kingdom
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
            'popular' => ['sometimes', 'nullable', 'boolean'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $regions = (new CacheDataManager(
                $this->regionDataService->setRelations(['image']),
                '_index',
                [$request]
            ))->getData();

            return $this->success('The list of regions', 200, [
                'regions' => $regions
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting regions list.', 404);
        }
    }

    /**
     * Get the events under a region
     *
     * @group Regions - Client
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
     * @urlParam slug string required The slug of the region. Example: midlands
     *
     * @param  Request        $request
     * @param  string         $slug
     * @return JsonResponse
     */
    public function events(Request $request, $slug): JsonResponse
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
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $region = (new CacheDataManager(
                $this->regionDataService,
                '_show',
                [$slug]
            ))->getData();

            $events = (new CacheDataManager(
                new EventClientDataService(),
                'getFilteredEventsByProperty',
                [$region, $request]
            ))->extraKey('events')->getData();

            return $this->success('The region details', 200, [
                'region' => $region,
                'events' => new EventResource($events),
                'map_data' => $this->getMapInfo($region->ref, $request),
                'price_range' => EventEventCategory::priceRange($region, $request)
            ]);
        } catch (ModelNotFoundException $e) {
            $origin = request()->headers->get('origin') ?? '';
            return (new RedirectManager(Region::class, $slug, 'slug', $origin))->redirect();
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('An error occurred while getting region details.', 404);
        }
    }

    public function getMapInfo($region, $request)
    {
        $count = Event::withOnly(['eventCategories' => function ($q) {
            $q->withoutAppends()->whereHas('site', function ($query) {
                $query->makingRequest();
            });
        }, 'eventThirdParties:id,ref,event_id,external_id,partner_channel_id', 'eventThirdParties' => function ($query) {
            $query->with(['eventCategories:id,ref,name', 'eventCategories' => function ($query) {
                $query->withoutAppends();
            }, 'partnerChannel:id,partner_id,ref,name', 'partnerChannel.partner:id,ref,name,code'])->whereNotNull('external_id')
                ->whereHas('partnerChannel', function ($query) {
                    $query->whereHas('partner', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        })->where('code', PredefinedPartnersEnum::LetsDoThis->value);
                    });
                })->has('eventCategories');
        }, 'image', 'gallery', 'address:id,locationable_id,locationable_type,address,coordinates'])
            ->appendsOnly([
                'local_registration_fee_range',
                'international_registration_fee_range',
                'date_range',
                'state',
                'registration_deadline_range',
                'website_registration_method'
            ])
            ->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                });
            })
            ->partnerEvent(Event::ACTIVE)
            ->where('status', Event::ACTIVE)
            ->whereHas('region', function ($query) use ($region) {
                $query->where('ref', $region);
            })
            ->orderByRaw("CASE WHEN (SELECT start_date FROM event_event_category WHERE event_event_category.event_id = events.id ORDER BY start_date
                LIMIT 1) >= CURDATE() THEN 0 ELSE 1 END")
            ->orderBy(
                EventEventCategory::select('start_date')
                    ->whereColumn('event_id', 'events.id')
                    ->orderBy('start_date')
                    ->limit(1),
                'asc'
            )
            ->select('events.id', 'events.ref', 'events.name', 'events.slug', 'events.registration_method','events.status')
            ->get();

        $mapData = $count;
        $groupedData = [];

        foreach ($mapData as $event) {
            // Check if 'address' exists and has 'latitude' and 'longitude'
            if (isset($event['address']) && isset($event['address']['latitude']) && isset($event['address']['longitude'])) {
                $latitude = $event['address']['latitude'];
                $longitude = $event['address']['longitude'];
                $event_name = $event->name;
                $event_slug = $event->slug;
                $key = $latitude . ',' . $longitude;

                if (isset($groupedData[$key])) {
                    $groupedData[$key]['count']++;
                } else {
                    $groupedData[$key] = [
                        'event_name' => $event_name,
                        'slug' => $event_slug,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'count' => 1,
                    ];
                }
            }
        }
        return $groupedData;
    }

    /**
     * Create a region
     * @group Regions
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        try {
            //            $sites = $this->regionDataService->sites();
            $sites = (new CacheDataManager(
                $this->regionDataService,
                'sites'
            ))->getData();

            return $this->success('Create a region', 200, [
                'sites' => $sites,
                'robots' => MetaRobotsEnum::_options()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting sites list.', 404);
        }
    }

    /**
     * Store a region
     * @group Regions
     * @param  StoreRegionRequest $request
     * @return JsonResponse
     */
    public function store(StoreRegionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $region = $this->regionDataService->store($request);

            DB::commit();

            return $this->success('Successfully created the region!', 200, [
                'region' => $region
            ]);
        } catch (QueryException | FileException | \Exception $e) {
            Log::error($e);
            DB::rollback();

            return $this->error('Unable to create the region! Please try again', 406, $e->getMessage());
        }
    }

    /**
     * Edit a region
     * @group Regions
     * @urlParam region_ref string required The ref of the region. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string $ref
     * @return JsonResponse
     */
    public function edit(string $ref): JsonResponse
    {
        try {
            $_region = (new CacheDataManager(
                $this->regionDataService,
                'edit',
                [$ref]
            ))->getData();

            //            $sites = $this->regionDataService->sites();

            $sites = (new CacheDataManager(
                $this->regionDataService,
                'sites'
            ))->extraKey('sites')->getData();

            return $this->success('Edit the region', 200, [
                'region' => $_region,
                'sites' => $sites,
                'action_messages' => Region::$actionMessages,
                'robots' => MetaRobotsEnum::_options()
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->error('The region was not found!', 404);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while getting region details.', 404);
        }
    }

    /**
     * Update a region
     * @group Regions
     * @urlParam region_ref string required The ref of the region. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  UpdateRegionRequest  $request
     * @param  Region               $region
     * @return JsonResponse
     */
    public function update(UpdateRegionRequest $request, Region $region): JsonResponse
    {
        try {
            DB::beginTransaction();

            $_region = $this->regionDataService->update($request, $region->ref);

            DB::commit();

            return $this->success('Successfully updated the region!', 200, [
                'region' => $_region
            ]);
        } catch (QueryException $e) {
            Log::error($e);
            DB::rollback();

            return $this->error('Unable to update the region! Please try again.', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return $this->error('The region was not found!', 404);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollback();

            return $this->error('An error occurred while updating region', 404);
        }
    }

    /**
     * Get a region's details.
     * @group Regions
     * @urlParam region_ref string required The ref of the region. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string        $region
     * @return JsonResponse
     */
    public function show(string $region): JsonResponse
    {
        try {
            $_region = (new CacheDataManager(
                $this->regionDataService,
                'show',
                [$region]
            ))->getData();

            return $this->success('The region details', 200, [
                'region' => $_region
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->error('The region was not found!', 404);
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error('An error occurred while fetching region details.', 404);
        }
    }

     /**
     * Mark as published one or many regions
     *
     * @group regions
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with regions. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsPublished(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsPublishedValidationRules('regions'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();

            $this->regionDataService->markAsPublished($request->ids);

            DB::commit();

            return $this->success('Successfully marked as published the region(s)!', 200);
        } catch (\Exception $e) {
            DB::rollback();

            return $this->error('Unable to mark as published the region(s)! Please try again.', 406, $e->getMessage());
        }
    }

    /**
     * Mark as draft one or many regions
     *
     * @group Regions
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with regions. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsDraft(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsDraftValidationRules('regions'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();

            $this->regionDataService->markAsDraft($request->ids);

            DB::commit();

            return $this->success('Successfully marked as draft the region(s)!', 200);
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to mark as draft the region(s)! Please try again.', 406, $e->getMessage());
        }
    }

    /**
     * Delete one or many regions
     *
     * @group Regions
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with regions. Example: [1,2]
     *
     * @param  RegionDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(RegionDeleteRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $this->regionDataService->destroy($request->validated('ids'));

            DB::commit();

            return $this->success('Successfully deleted the region(s)', 200);
        } catch (QueryException $e) {
            Log::error($e);
            DB::rollback();

            return $this->error('Unable to delete the region! Please try again.', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return $this->error('The region was not found!', 404);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollback();

            return $this->error('An error occurred while deleting the region.', 406);
        }
    }

    /**
     * Restore One or many regions
     *
     * @group Regions
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with regions. Example: [1,2]
     *
     * @param  RegionRestoreRequest $request
     * @return JsonResponse
     */
    public function restore(RegionRestoreRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $this->regionDataService->restore($request->validated('ids'));

            DB::commit();

            return $this->success('Successfully restored the region(s)', 200);
        } catch (QueryException $e) {
            Log::error($e);
            DB::rollback();

            return $this->error('Unable to restore the region! Please try again.', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return $this->error('The region was not found!', 404);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollback();

            return $this->error('An error occurred while restoring the region.', 406);
        }
    }

    /**
     * Delete one or many regions (Permanently)
     * Only the administrator can delete a region permanently.
     *
     * @group Regions
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam ids string[] required An array list of ids associated with regions. Example: [1,2]
     *
     * @param  RegionDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(RegionDeleteRequest $request): JsonResponse
    {
        if (!AccountType::isAdmin()) { // Only the administrator can delete an event permanently.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        try {
            DB::beginTransaction();

            $this->regionDataService->destroyPermanently($request->validated('ids'));

            DB::commit();

            return $this->success('Successfully deleted the region(s)', 200);
        } catch (QueryException $e) {
            Log::error($e);
            DB::rollback();

            return $this->error('Unable to delete the region(s) permanently! Please try again.', 406, $e->getMessage());
        } catch (ModelNotFoundException $e) {
            DB::rollback();

            return $this->error('The region was not found!', 404);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollback();

            return $this->error('An error occurred while deleting the region(s) permanently.', 406);
        }
    }

    /**
     * Delete the region's image
     * @group Regions
     * @urlParam region_ref string required The ref of the region. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam upload_ref string required The ref of the upload. Example: 97ad9df6-d927-4a44-8fec-3daacee89678
     *
     * @param Region $region
     * @param Upload $upload
     * @return JsonResponse
     */
    public function removeImage(Region $region, Upload $upload): JsonResponse
    {
        try {
            $_region = $this->regionDataService->removeImage($region, $upload);

            CacheDataManager::flushAllCachedServiceListings($this->regionDataService);

            return $this->success('Successfully deleted the image!', 200, [
                'region' => $_region->load(['site', 'image', 'gallery'])
            ]);
        } catch (QueryException | \Exception $e) {
            Log::error($e);

            return $this->error('Unable to delete the image! Please try again', 406);
        }
    }

    /**
     * Export regions
     * @group Regions
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam per_page integer Items per page No-example
     *
     * @param RegionListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(RegionListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->regionDataService->setRelations(['meta'])->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error("An error occurred while exporting regions data", 404);
        }
    }

    /**
     * Delete One/Many FAQs
     *
     * Delete multiple Region FAQs by specifying their ids.
     *
     * @urlParam region_ref string required The ref of the region. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @bodyParam faqs_ids string[] required The list of ids associated with specific region FAQs ids. Example: [1,2]

     * @param DeleteRegionFaqsRequest $request
     * @param Region $region
     * @return JsonResponse
     */
    public function destroyManyFaqs(DeleteRegionFaqsRequest $request, Region $region): JsonResponse
    {
        try {
            $this->faqRepository->destroyManyFaqs($request->validated(), $region);

            CacheDataManager::flushAllCachedServiceListings($this->regionDataService);

            return $this->success('Region FAQ(s) has been deleted.', 200, [
                'region' =>  new RegionResource($region->load(['faqs']))
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while deleting specified region FAQ(s). Please try again.', 400);
        }
    }

    /**
     * Delete One/Many FAQ Details
     *
     * Delete multiple Page FAQ details by specifying their ids.
     *
     * @urlParam region_ref string required The ref of the region. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam faq_ref string required The ref of the faq. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @bodyParam faq_details_ids string[] required The list of ids associated with specific page faq_details ids. Example: [1,2]
     *
     * @param  DeleteFaqDetailsRequest $request
     * @param  Faq $faq
     * @param  Region $region
     * @return JsonResponse
     */
    public function destroyManyFaqDetails(DeleteFaqDetailsRequest $request, Region $region, Faq $faq): JsonResponse
    {
        try {
            $this->faqRepository->destroyManyFaqDetails($request->validated(), $faq);

            CacheDataManager::flushAllCachedServiceListings($this->regionDataService);

            return $this->success('Region Faqs Details(s) has been deleted', 200, [
                'region' =>  new RegionResource($region->load(['faqs']))
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while deleting specified region FAQ details.', 400);
        }
    }

    /**
     * Remove faq details image
     *
     * @param  Region $region
     * @param  Faq $faq
     * @param  FaqDetails $faqDetails
     * @param  string $upload_ref
     * @return JsonResponse
     */
    public function removeFaqDetailImage(Region $region, Faq $faq, FaqDetails $faqDetails, string $upload_ref): JsonResponse
    {
        try {
            $this->faqRepository->removeImage($faqDetails, $upload_ref);

            CacheDataManager::flushAllCachedServiceListings($this->regionDataService);

            return $this->success('Successfully removed the image!', 200, [
                'region' =>  $region->load(['faqs'])
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->error('The image was not found!', 404);
        } catch (QueryException | \Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while deleting images.', 400);
        }
    }
}
