<?php

namespace App\Http\Controllers\Portal;
use App\Enums\PredefinedPartnersEnum;
use App\Models\Combination;
use App\Models\Region;
use App\Models\Uploadable;
use DB;
use Auth;
use Rule;
use Excel;
use Storage;
use Validator;
use Carbon\Carbon;
use App\Models\Medal;
use App\Models\Upload;
use App\Traits\Response;
use Illuminate\Http\Request;
use App\Facades\ClientOptions;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\AccountType;
use App\Services\RedirectManager;
use App\Http\Controllers\Controller;
use App\Http\Resources\SiteResource;
use App\Modules\Setting\Models\Site;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use App\Modules\Event\Resources\EventResource;
use App\Http\Resources\MedalResource;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Modules\Event\Resources\EventCategoryResource;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\Http\Requests\DeleteFaqDetailsRequest;
use App\Http\Requests\StoreEventCategoryRequest;
use App\Http\Requests\UpdateEventCategoryRequest;
use App\Http\Requests\DeleteEventCategoryFaqsRequest;
use App\Modules\Event\Requests\EventCategoryDeleteRequest;
use App\Modules\Event\Requests\EventCategoryRestoreRequest;
use App\Modules\Event\Requests\NationalAverageDeleteRequest;
use App\Modules\Event\Requests\EventCategoryAllQueryParamsRequest;
use App\Modules\Event\Requests\EventCategoryListingQueryParamsRequest;

use App\Traits\SiteTrait;
use App\Traits\HelperTrait;
use App\Traits\DownloadTrait;
use App\Traits\PaginationTrait;
use App\Traits\SingularOrPluralTrait;
use App\Traits\CustomPaginationTrait;

use App\Enums\GenderEnum;
use App\Enums\ListTypeEnum;
use App\Enums\EventStateEnum;
use App\Enums\MetaRobotsEnum;
use App\Enums\OrderByDirectionEnum;
use App\Enums\EventCategoryVisibilityEnum;
use App\Enums\EventCategoriesListOrderByFieldsEnum;

use App\Repositories\FaqRepository;
use Illuminate\Support\Facades\Log;
use App\Filters\FaqsFilter;
use App\Filters\MedalsFilter;
use App\Filters\DeletedFilter;
use App\Filters\EventCategoriesOrderByFilter;

use App\Exports\EventCategoryCsvExport;
use App\Services\EventListingService;
use App\Services\DefaultQueryParamService;

use App\Models\Faq;
use App\Models\FaqDetails;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Event\Models\NationalAverage;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EventCategoryDataService;
use App\Services\DataServices\EventClientDataService;
use App\Services\DataServices\EventDataService;
use App\Services\DataServices\GlobalSearchDataService;
use App\Services\DataServices\PartnerEventDataService;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\FileManager\Traits\UploadModelTrait;
use App\Traits\DraftCustomValidator;
use App\Modules\Event\Models\Event;

/**
 * @group Event Categories
 * Manages event categories on the application
 * @authenticated
 */
class EventCategoryController extends Controller
{
    use Response, SiteTrait, HelperTrait, UploadModelTrait, SingularOrPluralTrait, DownloadTrait, PaginationTrait, CustomPaginationTrait, DraftCustomValidator;

    /*
    |--------------------------------------------------------------------------
    | Event Category Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with event categories. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected FaqRepository $faqRepository, protected EventCategoryDataService $eventCategoryDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_event_categories', [
            'except' => [
                'getCategoriesCombination',
                'eventFetchBySlugName',
                'getPopularCombination',
                'getCustomFilterMenus',
                'all',
                '_index',
                'events'
            ]
        ]);
    }

    /**
     * Paginated event categories for dropdown fields.
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam with_setting_custom_fields bool Add the site settings custom fields to the data. No-example
     * @queryParam for string Only return the ones that are associated with the participants entries. Should be one of entries. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param EventCategoryAllQueryParamsRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function all(EventCategoryAllQueryParamsRequest $request): JsonResponse
    {
        $categories = (new CacheDataManager(
            $this->eventCategoryDataService,
            'all',
            [$request]
        ))->getData();

        return $this->success('All event categories', 200, [
            'event_categories' => new EventCategoryResource($categories)
        ]);
    }

    /**
     * The list of event categories
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam visibility string Filter by visibility. Must be one of public, private. No-example
     * @queryParam faqs string Specifying the inclusion of ONLY pages with associated FAQs. Should be one of with, without. Example: with
     * @queryParam medals string Specifying the inclusion of ONLY pages with associated medals. Should be one of with, without. Example: with
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,visibility:asc,created_at:desc
     * @queryParam drafted string Specifying how to interact with drafted items. Example: with
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param EventCategoryListingQueryParamsRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(EventCategoryListingQueryParamsRequest $request): JsonResponse
    {
        $categories = (new CacheDataManager(
            $this->eventCategoryDataService->setLoadRedirect(true),
            'getPaginatedList',
            [$request]
        ))->getData();

        return $this->success('The list of categories', 200, [
            'event_categories' => new EventCategoryResource($categories),
            'options' => ClientOptions::only('event_categories', [
                'visibilities',
                'drafted',
                'faqs',
                'medals',
                'deleted',
                'order_by',
                'order_direction'
            ]),
            'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::EventCategories))
                ->setParams(['order_by' => EventCategoriesListOrderByFieldsEnum::Name->value . ":" . OrderByDirectionEnum::Ascending->value])
                ->getDefaultQueryParams(),
            'action_messages' => EventCategory::$actionMessages
        ]);
    }

    public function getCategoriesCombination(): JsonResponse
    {
        try {
            $categories = EventCategory::select('id', 'ref', 'name', 'slug', 'site_id')
                ->whereHas('site', function ($query) {
                    $query->makingRequest();
                })
                ->whereIn('name', ['5K', '10K', 'Half Marathon', '20 Mile', 'Marathon'])
                ->orderByRaw("FIELD(name, '5K', '10K', 'Half Marathon', '20 Mile', 'Marathon')");

            if (AccountType::isParticipant()) {
                $categories->whereHas('events', function ($query) {
                    $query->estimated(Event::INACTIVE)
                        ->archived(Event::INACTIVE)
                        ->where('status', Event::ACTIVE)
                        ->where('end_date', '>', Carbon::now());
                });
            }

            $categories = $categories->get()->map(function ($category) {
                $category->type = 'distance';
                return $category;
            });

            $combinations = Combination::select('id', 'site_id', 'ref', 'name', 'slug', 'path')
                ->where('site_id', clientSiteId())
                ->whereIn('name', ['Triathlons and Duathlons', 'Trail Races', 'Other Distances'])
                ->orderByRaw("FIELD(name, 'Other Distances', 'Trail Races', 'Triathlons and Duathlons')");
            $combinations = $combinations->get()->map(function ($combination) {
                $combination->type = 'combination';
                return $combination;
            });
            $mergedData = $categories->merge($combinations);

            return response()->json([
                'code' => 200,
                'message' => 'Data retrieved successfully.',
                'data' => $mergedData
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while retrieving data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function eventFetchBySlugName(Request $request): JsonResponse
    {
        try {
            $ref = $request->ref;
            $combination = Combination::query()
                ->withOnly(['image', 'gallery', 'faqs', 'meta'])
                ->withCount([
                    'events as active_events_count' => function ($query) {
                        $query->state(EventStateEnum::Live);
                    }
                ])
                ->where('ref', '=', $ref)
                ->when(request()->draft, fn($query) => $query->onlyDrafted())
                ->firstOrFail();
            $eventListingService = new EventListingService(
                Event::query()
                    ->state(EventStateEnum::Live)
                    ->partnerEvent(Event::ACTIVE)
                    ->where(function ($q1) use ($combination) {
                        $regionId = array_column(json_decode($combination->region_id, true) ?? [], 'id');
                        $cityId = array_column(json_decode($combination->city_id, true) ?? [], 'id');
                        $venueId = array_column(json_decode($combination->venue_id, true) ?? [], 'id');
                        $seriesId = array_column(json_decode($combination->series_id, true) ?? [], 'id');
                        $eventCategoryId = array_column(json_decode($combination->event_category_id, true) ?? [], 'id');
                        $month = !empty($combination->month) ? date('n', strtotime($combination->month)) : null;
                        $year = $combination->year;

                        $q1->when(!empty($regionId), fn($q2) => $q2->whereIn('region_id', $regionId))
                            ->when(!empty($cityId), fn($q2) => $q2->whereIn('city_id', $cityId))
                            ->when(!empty($venueId), fn($q2) => $q2->whereIn('venue_id', $venueId))
                            ->when(!empty($seriesId), fn($q2) => $q2->whereIn('serie_id', $seriesId))
                            ->when(!empty($eventCategoryId), fn($q2) => $q2->whereHas('eventCategories', fn($q3) => $q3->whereIn('event_categories.id', $eventCategoryId)))
                            ->when(!empty($month), fn($q2) => $q2->whereHas('eventCategories', fn($q3) => $q3->whereMonth('start_date', '=', $month)))
                            ->when(!empty($year), fn($q2) => $q2->whereHas('eventCategories', fn($q3) => $q3->whereYear('start_date', '=', $year)));
                    }),
                $request->all()
            );

            $filteredEvents = $eventListingService->getFilteredClientCollection();
            return response()->json($filteredEvents, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Combination not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function getCustomFilterMenus(): JsonResponse
    {
        try {
            $siteId = clientSiteId();
            $categories = EventCategory::select('id', 'ref', 'name', 'slug', 'promote_flag', 'priority_number')
                ->withoutAppends()
                ->where('site_id', $siteId)
                ->where('promote_flag', 1)
                ->orderBy('priority_number', 'ASC')
                ->get() ->map(function ($item) {
                    $item->type = 'category';
                    return $item;
                });
            $regions = Region::select('id', 'ref', 'name', 'slug', 'promote_flag', 'priority_number')
                ->withoutAppends()
                ->where('site_id', $siteId)
                ->where('promote_flag', 1)
                ->orderBy('priority_number', 'ASC')
                ->get()->map(function ($item) {
                    $item->type = 'region';
                    return $item;
                });
            $combinations = Combination::select('id', 'ref', 'name', 'slug', 'promote_flag', 'priority_number', 'path')
                ->setEagerLoads([])
                ->withoutAppends()
                ->where('site_id', $siteId)
                ->where('promote_flag', 1)
                ->orderBy('priority_number', 'ASC')
                ->get()->map(function ($item) {
                    $item->type = 'combination';
                    return $item;
                });
         $allData = $categories->concat($regions)->concat($combinations)->sortBy('priority_number')->values()->all();
            return response()->json([
                'code' => 200,
                'message' => 'Data retrieved successfully.',
                'data' => $allData
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching combinations: ' . $e->getMessage());

            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while retrieving data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function getPopularCombination(): JsonResponse
    {
        try {
            $combinations = Combination::select('id', 'site_id', 'ref', 'name', 'slug', 'path')
                ->where('site_id', clientSiteId())
                ->whereIn('name', ['Triathlons and Duathlons', 'Trail Races', 'Other Distances'])
                ->orderByRaw("FIELD(name, 'Other Distances', 'Trail Races', 'Triathlons and Duathlons')")
                ->get()
                ->map(function ($combination) {
                    $combination->type = 'combination';
                    $combination->active_events_count = $this->getCombinationCount($combination->path);
                    return $combination;
                });

            return response()->json([
                'code' => 200,
                'message' => 'Data retrieved successfully.',
                'data' => $combinations
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching combinations: ' . $e->getMessage());

            return response()->json([
                'code' => 500,
                'message' => 'An error occurred while retrieving data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCombinationCount($path)
    {
        $combination = Combination::query()
            ->withOnly(['image', 'gallery', 'faqs', 'meta'])
            ->withCount([
                'events as active_events_count' => function ($query) {
                    $query->state(EventStateEnum::Live);
                }
            ])
            ->where('path', '=', $path)
            ->when(request()->draft, fn($query) => $query->onlyDrafted())
            ->firstOrFail();

        $count = Event::query()
            ->state(EventStateEnum::Live)
            ->partnerEvent(Event::ACTIVE)
            ->where(function ($q1) use ($combination) {
                $regionId = array_column(json_decode($combination->region_id, true) ?? [], 'id');
                $cityId = array_column(json_decode($combination->city_id, true) ?? [], 'id');
                $venueId = array_column(json_decode($combination->venue_id, true) ?? [], 'id');
                $seriesId = array_column(json_decode($combination->series_id, true) ?? [], 'id');
                $eventCategoryId = array_column(json_decode($combination->event_category_id, true) ?? [], 'id');

                $q1->when(!empty($regionId), function ($q2) use ($regionId) {
                    $q2->whereIn('region_id', $regionId);
                })
                    ->when(!empty($cityId), function ($q2) use ($cityId) {
                        $q2->whereIn('city_id', $cityId);
                    })
                    ->when(!empty($venueId), function ($q2) use ($venueId) {
                        $q2->whereIn('venue_id', $venueId);
                    })
                    ->when(!empty($seriesId), function ($q2) use ($seriesId) {
                        $q2->whereIn('serie_id', $seriesId);
                    })
                    ->when(!empty($eventCategoryId), function ($q2) use ($eventCategoryId) {
                        $q2->whereHas('eventCategories', function ($q3) use ($eventCategoryId) {
                            $q3->whereIn('event_categories.id', $eventCategoryId);
                        });
                    });
            })
            ->count() ?? 0;
        return $count;
    }

    /**
     * The list of event categories
     *
     * @group Event Categories - Client
     * @unauthenticated
     *
     * @queryParam term string Filter by term. The term to search for. No-example
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
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $categories = EventCategory::with(['image', 'gallery'])
            ->withoutAppends()
            ->withCount(['events as active_events_count' => function ($query) {
                $query->state(EventStateEnum::Live)->partnerEvent(Event::ACTIVE);
            }])->whereHas('site', function ($query) {
                $query->makingRequest();
            })->visibility(EventCategoryVisibilityEnum::Public);

            if ($request->filled('popular') && $request->popular) {
                $categories = $categories->whereIn('name', ['5K', '10K', 'Half Marathon', '20 Mile', 'Marathon'])
                    ->orderByRaw("FIELD(name, '5K', '10K', 'Half Marathon', '20 Mile', 'Marathon')")
                    ->orderByDesc("active_events_count");
            }

        if ($request->filled('term')) {
            $categories = $categories->where('name', 'LIKE', '%' . $request->term . '%');
        }

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $categories = $categories->paginate($perPage);

        return $this->success('The list of categories', 200, [
            'event_categories' => new EventCategoryResource($categories)
        ]);
    }

    /**
     * Get the events under a category
     *
     * @group Event Categories - Client
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
     * @urlParam _category string required The slug of the event category. Example: marathons
     *
     * @param  Request       $request
     * @param  string        $_category
     * @return JsonResponse
     */
    public function events(Request $request, string $_category): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'nullable', 'string'],
            'start_date' => ['sometimes', 'nullable', 'date_format:d-m-Y'],
            'end_date' => ['sometimes', 'nullable', 'date_format:d-m-Y', 'after_or_equal:start_date'],
            'price' => ['sometimes', 'nullable', 'array', 'size:2'],
            'price.*' => ['integer'],
            'category' => ['sometimes', 'nullable', 'string', Rule::exists('event_categories', 'ref')->where(
                function ($query) {
                    return $query->where("site_id", static::getSite()?->id);
                }
            )],
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
            $category = (new CacheDataManager(
                $this->eventCategoryDataService,
                '_show',
                [$_category]
            ))->getData();

            $events = (new CacheDataManager(
                new EventClientDataService(),
                'getFilteredEventsByProperty',
                [$category, $request]
            ))->extraKey('events')
            ->getData();

            return $this->success('The event category details', 200, [
                'event_category' => new EventCategoryResource($category),
                'events' => $events,
                'map_data' => $this->getMapInfo($category, $request),
                'price_range' => EventEventCategory::priceRange($category, $request)
            ]);
        } catch (ModelNotFoundException $e) {
            $origin = request()->headers->get('origin') ?? '';
            return (new RedirectManager(EventCategory::class, $_category, 'slug', $origin))->redirect();
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('An error occurred while fetching the event category!', 500);
        }
    }

    public function getMapInfo($category, $request)
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
            ->whereHas('eventCategories', function ($query) use ($category) {
                $query->where('event_categories.ref', $category->ref);
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
        return $groupedData;
    }

    /**
     * Create a new event category
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        $sites = Site::select('ref', 'domain', 'name')
            ->hasAccess()
            ->makingRequest()
            ->get();

        return $this->success('Create an event category', 200, [
            'sites' => $sites,
            'robots' => MetaRobotsEnum::_options()
        ]);
    }

    /**
     * Store the new event category
     *
     * @param  StoreEventCategoryRequest  $request
     * @return JsonResponse
     */
    public function store(StoreEventCategoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $site = Site::where('ref', $request->site_ref)->first();

            $category = new EventCategory();
            $category->fill($request->all());
            $category->site_id = $site->id;
            $category->save();

            if ($request->filled('image')) { // Save the event category's image
                $this->attachSingleUploadToModel($category, $request->image);
            }

            if ($request->filled('gallery')) { // Save the models gallery
                $this->attachMultipleUploadsToModel($category, $request->gallery);
            }

            $this->saveMetaData($request, $category); // Save meta data

            if ($request->filled('faqs')) {
                $this->faqRepository->store($request->validated(), $category);
            }

            DB::commit();
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to create the event category! Please try again', 406, $e->getMessage());
        } catch (FileException $e) {

            return $this->error('Unable to create the event category! Please try again', 406, $e->getMessage());
        }
        return $this->success('Successfully created the event category!', 201, new EventCategoryResource($category->load(['site', 'meta', 'image', 'gallery', 'faqs'])));
    }

    /**
     * Get an event category
     *
     * @urlParam category_ref string required The ref of the event category. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string        $category
     * @return JsonResponse
     */
    public function show(string $category): JsonResponse
    {
        try {
            $_category = EventCategory::with(['site', 'image', 'gallery', 'faqs'])
                ->withCount('events')
                ->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                })->where('ref', $category)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {

            return $this->error('The event category was not found!', 404);
        }

        return $this->success('The event category details', 200, new EventCategoryResource($_category));
    }

    /**
     * Edit an event category
     *
     * @urlParam category_ref string required The ref of the event category. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string $category
     * @return JsonResponse
     */
    public function edit(string $category): JsonResponse
    {
        try {
            $_category = (new CacheDataManager(
                $this->eventCategoryDataService,
                'edit',
                [$category]
            ))->getData();

            $sites = Site::select('ref', 'domain', 'name') // Remove this and have the frontend fetch it from the sites/all endpoint
                ->hasAccess()
                ->makingRequest()
                ->get();
        } catch (ModelNotFoundException $e) {
            return $this->error('The event category was not found!', 404);
        }

        return $this->success('Edit the event category', 200, [
            'category' => new EventCategoryResource($_category),
            'sites' => new SiteResource($sites),
            'action_messages' => EventCategory::$actionMessages,
            'robots' => MetaRobotsEnum::_options()
        ]);
    }

    /**
     * Update an event category
     *
     * @urlParam category_ref string required The ref of the event category. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  UpdateEventCategoryRequest  $request
     * @param  EventCategory               $category
     * @return JsonResponse
     */
    public function update(UpdateEventCategoryRequest $request, EventCategory $category): JsonResponse
    {
        try {
            $_category = EventCategory::with(['faqs', 'meta', 'site' => function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            }, 'image', 'gallery'])
                ->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                })->where('ref', $category->ref)
                ->withDrafted()
                ->firstOrFail();

            try {
                DB::beginTransaction();

                if ($request->filled('site_ref')) {
                    $site = Site::where('ref', $request->site_ref)->first();
                    $request['site_id'] = $site->id;
                }

                $_category->update($request->all());

                if ($request->filled('image')) { // Save the event category's image
                    $this->attachSingleUploadToModel($_category, $request->image);
                }

                if ($request->filled('gallery')) { // Save the models gallery
                    $this->attachMultipleUploadsToModel($_category, $request->gallery);
                }

                $this->saveMetaData($request, $_category); // Save meta data

                if ($request->filled('faqs')) {
                    $this->faqRepository->update($request->validated(), $_category);
                }

                DB::commit();

                CacheDataManager::flushAllCachedServiceListings($this->eventCategoryDataService);
                CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                CacheDataManager::flushAllCachedServiceListings(new EventDataService);
                CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to update the event category! Please try again.', 406, $e->getMessage());
            } catch (FileException $e) {

                return $this->error('Unable to update the event category! Please try again', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The event category was not found!', 404);
        }

        $category = $this->eventCategoryDataService->modelWithAppendedAnalyticsAttribute($_category->load(['faqs', 'meta', 'site', 'image', 'gallery']));

        return $this->success('Successfully updated the event category', 200, new EventCategoryResource($category));
    }

    /**
     * Mark one or more event categories as published
     *
     * @bodyParam ids string[] required The list of ids associated with categories. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsPublished(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsPublishedValidationRules('event_categories'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            EventCategory::whereIntegerInRaw('id', $request->ids)
                ->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                })->onlyDrafted()
                ->markAsPublished();

            CacheDataManager::flushAllCachedServiceListings($this->eventCategoryDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
            CacheDataManager::flushAllCachedServiceListings(new EventDataService);
            CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
            (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();

            return $this->success('Successfully marked as published the event category(s)!');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while updating EventCategory.', 400);
        }
    }


    /**
     * Mark one or more event categories as draft
     *
     * @bodyParam ids string[] required The list of ids associated with event categories. Example: [1,2]
     *
     * @param EventCategory $EventCategory
     * @return JsonResponse
     */
    public function markAsDraft(Request $request)
    {
        $validator = Validator::make($request->all(), $this->markAsDraftValidationRules('event_categories'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            EventCategory::whereIntegerInRaw('id', $request->ids)->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->markAsDraft();

            CacheDataManager::flushAllCachedServiceListings($this->eventCategoryDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
            CacheDataManager::flushAllCachedServiceListings(new EventDataService);
                CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();

            return $this->success('Successfully marked as draft the event category(s).');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while updating EventCategory.', 400);
        }
    }

    /**
     * Delete one or many event categories
     *
     * @param  EventCategoryDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(EventCategoryDeleteRequest $request): JsonResponse
    {
        try {
            $categories = EventCategory::with(['site', 'image', 'gallery', 'faqs'])
                ->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                })->whereIn('ref', $request->refs)
                ->withDrafted()
                ->get();

            if (!$categories->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($categories as $category) {
                    $category->delete();
                }

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to delete the event ' . static::singularOrPlural(['category', 'categories'], $request->refs) . '! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The event ' . static::singularOrPlural(['category was', 'categories were'], $request->refs) . ' not found!', 404);
        }

        return $this->success('Successfully deleted the event ' . static::singularOrPlural(['category', 'categories'], $request->refs), 200, new EventCategoryResource($categories->load(['image', 'gallery'])));
    }

    /**
     * Restore one or many event categories
     *
     * @param  EventCategoryRestoreRequest $request
     * @return JsonResponse
     */
    public function restore(EventCategoryRestoreRequest $request): JsonResponse
    {
        try {
            $categories = EventCategory::with(['site', 'image', 'gallery'])
                ->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                })->onlyTrashed()
                ->whereIn('ref', $request->refs)
                ->withDrafted()
                ->get();

            if (!$categories->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($categories as $category) {
                    $category->restore();
                }

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to restore the event ' . static::singularOrPlural(['category', 'categories'], $request->refs) . '! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The event ' . static::singularOrPlural(['category was', 'categories were'], $request->refs) . ' not found!', 404);
        }

        return $this->success('Successfully restored the event(s) ' . static::singularOrPlural(['category', 'categories'], $request->refs), 200, new EventCategoryResource($categories->load(['image', 'gallery'])));
    }

    /**
     * Delete one or many event categories (Permanently)
     * Only the administrator can delete an event category permanently.
     *
     * @param  EventCategoryDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(EventCategoryDeleteRequest $request): JsonResponse
    {
        if (!AccountType::isAdmin()) { // Only the administrator can delete an event permanently.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $categories = EventCategory::with(['site', 'image', 'gallery'])
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            });

        try {
            $categories = $categories->whereIn('ref', $request->refs)
                ->withDrafted()
                ->withTrashed()
                ->get();

            if (!$categories->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($categories as $category) {
                    $category->forceDelete();
                }

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to delete the ' . static::singularOrPlural(['category', 'categories'], $request->refs) . ' permanently!', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The ' . static::singularOrPlural(['category was', 'categories were'], $request->refs) . ' not found!', 404);
        }

        return $this->success('Successfully deleted the ' . static::singularOrPlural(['category', 'categories'], $request->refs) . ' permanently', 200, new EventCategoryResource($categories->load(['image', 'gallery'])));
    }

    /**
     * Remove the event category's image
     *
     * @urlParam category_ref string required The ref of the event category. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam upload_ref string required The ref of the upload. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string         $category
     * @param  string         $upload_ref
     * @return JsonResponse
     */
    public function removeImage(string $category, string $upload_ref): JsonResponse
    {
        $eventCategory = EventCategory::with('uploads')
            ->withDrafted()
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            });

        try {
            $eventCategory = $eventCategory->where('ref', $category)
                ->firstOrFail();

            try {
                $_upload = $eventCategory->uploads()
                    ->where('ref', $upload_ref)
                    ->firstOrFail();

                try {
                    //$this->detachUpload($eventCategory, $_upload);

                    Uploadable::where('uploadable_type', '=', EventCategory::class)
                        ->where('upload_id', $_upload->id)
                        ->where('uploadable_id', $eventCategory->id)
                        ->delete();

                    CacheDataManager::flushAllCachedServiceListings($this->eventCategoryDataService);
                    CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                    CacheDataManager::flushAllCachedServiceListings(new EventDataService);
                    CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                    (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
                } catch (QueryException $e) {
                    return $this->error('Unable to delete the image! Please try again', 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {
                return $this->error('The image was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event category was not found!', 404);
        }

        $eventCategory->load(['image', 'gallery']);

        return $this->success('Successfully deleted the image!', 200, new EventCategoryResource($eventCategory));
    }

    /**
     * Export event categories
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,visibility:asc,created_at:desc
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param EventCategoryListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(EventCategoryListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->eventCategoryDataService->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while exporting event categories data.', 400);
        }
    }

    /**
     * Create an event category's national average
     *
     * @urlParam category_ref string required The ref of the event category. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string        $category
     * @return JsonResponse
     *
     */
    public function createNationalAverage(string $category): JsonResponse
    {
        try {
            $_category = EventCategory::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $category)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->error('The event category was not found!', 404);
        }

        return $this->success('Create a national average', 200, [
            'category' => new EventCategoryResource($_category),
            'genders' => GenderEnum::_options()
        ]);
    }

    /**
     * Store the new event category national average
     *
     * @urlParam category_ref string required The ref of the event category. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  Request        $request
     * @param  string         $category
     * @return JsonResponse
     *
     */
    public function storeNationalAverage(Request $request, string $category): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // The gender. Must be one of male, female. Example: male
            'gender' => ['required', new Enum(GenderEnum::class)],
            // The year. Example: 2022
            'year' => ['required', 'digits:4', 'date_format:Y'],
            // The time. Example: 02:32:15
            'time' => ['required', 'date_format:H:i:s']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $_category = EventCategory::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $category)
                ->firstOrFail();

            try {
                $nationalAvg = $_category->nationalAverages()->firstOrNew([
                    'gender' => $request->gender,
                    'year' => $request->year,
                ]);

                $nationalAvg->fill($request->all());
                $nationalAvg->save();

                CacheDataManager::flushAllCachedServiceListings($this->eventCategoryDataService);
                CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                    CacheDataManager::flushAllCachedServiceListings(new EventDataService);
                    CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                    (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
            } catch (QueryException $e) {
                return $this->error('Unable to create the national average! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event category was not found!', 404);
        }

        return $this->success('Successfully created the national average!', 201, new EventCategoryResource($_category->load('nationalAverages')));
    }

    /**
     * Get an event category national average
     *
     * @urlParam category_ref string required The ref of the event category. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam nationalAverage_ref string required The ref of the national average. Example: 97b026f5-94a1-4e59-8ebe-12a26e2c76ae
     *
     * @param  string         $category
     * @param  string         $nationalAverage
     * @return JsonResponse
     */
    public function showNationalAverage(string $category, string $nationalAverage): JsonResponse
    {
        try {
            $_category = EventCategory::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $category)
                ->firstOrFail();

            try {
                $nationalAvg = $_category->nationalAverages()
                    ->where('ref', $nationalAverage)
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return $this->error('The national average was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event category was not found!', 404);
        }

        return $this->success('The event category national average details', 200, $nationalAvg);
    }

    /**
     * Edit an event category national average
     *
     * @urlParam category_ref string required The ref of the event category. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam nationalAverage_ref string required The ref of the national average. Example: 97b026f5-94a1-4e59-8ebe-12a26e2c76ae
     *
     * @param  string        $category
     * @param  string        $nationalAverage
     * @return JsonResponse
     */
    public function editNationalAverage(string $category, string $nationalAverage): JsonResponse
    {
        try {
            $_category = EventCategory::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $category)
                ->firstOrFail();

            try {
                $nationalAvg = $_category->nationalAverages()
                    ->where('ref', $nationalAverage)
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return $this->error('The national average was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event category was not found!', 404);
        }

        return $this->success('Edit the national average!', 200, [
            'national_average' => $nationalAvg,
            'category' => new EventCategoryResource($_category),
            'genders' => GenderEnum::_options()
        ]);
    }

    /**
     * Update an event category national average
     *
     * @urlParam category_ref string required The ref of the event category. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam nationalAverage_ref string required The ref of the national average. Example: 97b026f5-94a1-4e59-8ebe-12a26e2c76ae
     *
     * @param  Request       $request
     * @param  string        $category
     * @param  string        $nationalAverage
     * @return JsonResponse
     */
    public function updateNationalAverage(Request $request, string $category, string $nationalAverage): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // The gender. Must be one of male, female. Example: male
            'gender' => ['sometimes', 'required', new Enum(GenderEnum::class)],
            // The year. Example: 2022
            'year' => ['sometimes', 'required', 'digits:4', 'date_format:Y'],
            // The time. Example: 02:32:15
            'time' => ['sometimes', 'required', 'date_format:H:i:s']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $_category = EventCategory::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $category)
                ->firstOrFail();

            try {
                $nationalAvg = $_category->nationalAverages()
                    ->where('ref', $nationalAverage)
                    ->firstOrFail();

                try {
                    $nationalAvg->update($request->only(['gender', 'year', 'time']));

                    CacheDataManager::flushAllCachedServiceListings($this->eventCategoryDataService);
                    CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                    CacheDataManager::flushAllCachedServiceListings(new EventDataService);
                    CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                    (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
                } catch (QueryException $e) {
                    return $this->error('Unable to update the national average! Please try again.', 406);
                }
            } catch (ModelNotFoundException $e) {
                return $this->error('The national average was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event category was not found!', 404);
        }

        return $this->success('Successfully updated the national average!', 200, $nationalAvg);
    }

    /**
     * Delete one or many event category national averages
     *
     * @urlParam category_ref string required The ref of the event category. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  NationalAverageDeleteRequest  $request
     * @param  string                        $category
     * @return JsonResponse
     */
    public function destroyNationalAverage(NationalAverageDeleteRequest $request, string $category): JsonResponse
    {
        try {
            $_category = EventCategory::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->where('ref', $category)
                ->firstOrFail();

            try {
                $nationalAvgs = $_category->nationalAverages()
                    ->whereIn('ref', $request->refs)
                    ->get();

                if (!$nationalAvgs->count()) {

                    throw new ModelNotFoundException();
                }

                try {
                    DB::beginTransaction();

                    foreach ($nationalAvgs as $nationalAvg) {
                        $nationalAvg->delete();
                    }

                    DB::commit();

                    CacheDataManager::flushAllCachedServiceListings($this->eventCategoryDataService);
                    CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                    CacheDataManager::flushAllCachedServiceListings(new EventDataService);
                    CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                    (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
                } catch (QueryException $e) {
                    DB::rollback();

                    return $this->error('Unable to delete the national average(s)! Please try again.', 406);
                }
            } catch (ModelNotFoundException $e) {
                return $this->error('The national average(s) was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event category was not found!', 404);
        }

        return $this->success('Successfully deleted the national average(s)!', 200, $nationalAvgs);
    }

    /**
     * Get the event category medals.
     *
     * @urlParam category_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @queryParam term string Filter by term. The term to search for. No-example
     *
     * @param  Request  $request
     * @param  string   $category
     * @return void
     */
    public function medals(Request $request, string $category)
    {
        $validator = Validator::make($request->all(), [
            'term' => ['sometimes', 'nullable', 'string']
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422,  $validator->errors()->messages());
        }

        try {
            $category = EventCategory::with(['medals' => function ($query) {
                $query->with(['site', 'upload'])
                    ->when(request()->filled('term'), function ($query) {
                        $query->where('name', 'like', '%' . request()->term . '%');
                    })->withTrashed();
            }])->whereHas('site', function ($query) {
                $query->makingRequest();
            })->where('ref', $category)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->error('The event Category was not found!', 404);
        }

        return $this->success('Successfully retrieved event category medals.', 200, [
            'medals' => new MedalResource($category->medals),
            'query_params' => $request->all(),
            'action_messages' => Medal::$actionMessages
        ]);
    }

    /**
     * Delete One/Many Faqs
     *
     * Delete multiple event category FAQS by specifying their ids
     *
     * @urlParam category_ref string required The ref of the event category. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @bodyParam faqs_ids string[] required The list of ids associated with specific Event Category FAQs ids. Example: [1,2]
     *
     * @param  DeleteEventCategoryFaqsRequest  $request
     * @param  EventCategory                   $category
     * @return JsonResponse
     */
    public function destroyManyFaqs(DeleteEventCategoryFaqsRequest $request, EventCategory $category): JsonResponse
    {
        try {
            $this->faqRepository->destroyManyFaqs($request->validated(), $category);

            CacheDataManager::flushAllCachedServiceListings($this->eventCategoryDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
            CacheDataManager::flushAllCachedServiceListings(new EventDataService);
            CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
            (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while deleting specified event category FAQ(s).', 400);
        }

        return $this->success('Event Category FAQ(s) has been deleted.', 200, [
            'category' => new EventCategoryResource($category->load('faqs'))
        ]);
    }

    /**
     * Delete One/Many FAQ Details
     *
     * Delete multiple Page FAQ details by specifying their ids.
     *
     * @urlParam category_ref string required The ref of the event category. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam faq_ref string required The ref of the faq. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @bodyParam faq_details_ids string[] required The list of ids associated with specific event category faq_details ids. Example: [1,2]
     *
     * @param  DeleteFaqDetailsRequest $request
     * @param  Faq $faq
     * @param  EventCategory $category
     * @return JsonResponse
     */
    public function destroyManyFaqDetails(DeleteFaqDetailsRequest $request, EventCategory $category, Faq $faq): JsonResponse
    {
        try {
            $this->faqRepository->destroyManyFaqDetails($request->validated(), $faq);

            CacheDataManager::flushAllCachedServiceListings($this->eventCategoryDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
            CacheDataManager::flushAllCachedServiceListings(new EventDataService);
            CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
            (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while deleting specified event category FAQ details.', 400);
        }

        return $this->success('Event category Faqs Details(s) has been deleted', 200, [
            'category' => new EventCategoryResource($category->load('faqs'))
        ]);
    }

    /**
     * Remove faq details image
     *
     * @param  EventCategory $category
     * @param  Faq $faq
     * @param  FaqDetails $faqDetails
     * @param  string $upload_ref
     * @return JsonResponse
     */
    public function removeFaqDetailImage(EventCategory $category, Faq $faq, FaqDetails $faqDetails, string $upload_ref)
    {
        try {
            $this->faqRepository->removeImage($faqDetails, $upload_ref);

            CacheDataManager::flushAllCachedServiceListings($this->eventCategoryDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
            CacheDataManager::flushAllCachedServiceListings(new EventDataService);
            CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
            (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
        } catch (ModelNotFoundException $e) {
            return $this->error('The image was not found!', 404);
        }

        return $this->success('Successfully removed the image!', 200, [
            'category' =>  $category->load(['faqs'])
        ]);
    }
}
