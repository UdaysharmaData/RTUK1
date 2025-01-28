<?php

namespace App\Http\Controllers\Client;

use App\Services\RedirectManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Facades\ClientOptions;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EventClientDataService;
use App\Services\Analytics\Events\AnalyticsViewEvent;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Event\Models\EventCategoryEventThirdParty;

use App\Modules\Event\Resources\EventResource;

use App\Http\Requests\PaginationRequest;
use App\Modules\Event\Requests\CheckoutOnLDTRequest;
use App\Modules\Event\Requests\EventClientListingQueryParamsRequest;
use App\Modules\Event\Requests\EventClientCalendarQueryParamsRequest;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;
use App\Traits\SingularOrPluralTrait;
use App\Enums\PredefinedPartnersEnum;
use App\Modules\Setting\Enums\SiteEnum;

/**
 * @group Events - Client
 * The events on the application
 */
class EventController extends Controller
{
    use Response, SiteTrait, UploadTrait, SingularOrPluralTrait;

    /*
    |--------------------------------------------------------------------------
    | Event Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with events. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * @var bool
     */
    private Site|null $site;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected EventClientDataService $eventService)
    {
        parent::__construct();

        $this->site = static::getSite();
    }

    /**
     * The list of events
     *
     * @queryParam name string Filter by name. The term to search for. No-example
     * @queryParam category string Filter by event category slug. Example: marathons
     * @queryParam start_date string Filter by start_date. Must be a valid date in the format d-m-Y. Example: "22-02-2018"
     * @queryParam end_date string Filter by end_date. Must be a valid date in the format d-m-Y. Example: "22-02-2023"
     * @queryParam price integer[] Filter by a price range. Example: [12, 80]
     * @queryParam region string Filter by region ref. No-example
     * @queryParam city string Filter by city ref. No-example
     * @queryParam venue string Filter by venue ref. No-example
     * @queryParam address string Filter by address. No-example
     * @queryParam radius integer[] Filter by a location (address) radius (the area that spans the given location by the radius value provided). Example: [12, 80]
     * @queryParam virtual_events string Filter by virtual_events. Must be one of include, exclude, only. Example: include
     * @queryParam date string Filter by date. Must be one of newest, oldest, this_year, next_year, next_3_months, next_6_months, 2022-09, 2022-10. No-example
     * @queryParam skip integer The number of items to skip before taking the number of items specified by the take query param Example: 6
     * @queryParam take integer Number of items to return. Example 6
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param EventClientListingQueryParamsRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(EventClientListingQueryParamsRequest $request): JsonResponse
    {
        $response = (new CacheDataManager(
            $this->eventService,
            'getPaginatedList',
            [$request]
        ))->getData();

        return $this->success('The list of events', 200, [
            'events' => $response,
            'map_data' => $this->getMapInfo($request),
            'price_range' => EventEventCategory::priceRange()
        ]);
    }

    public function getMapInfo(Request $request)
    {
        // Months Array
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        // Base Query with Relationships
        $events = Event::select('id', 'ref', 'name', 'slug', 'registration_method', 'status')
            ->with([
                'eventCategories:id,ref,name',
                'eventThirdParties' => function ($query) {
                    $query->with([
                        'eventCategories:id,ref,name',
                        'partnerChannel:id,partner_id,ref,name',
                        'partnerChannel.partner:id,ref,name,code',
                    ])
                        ->whereNotNull('external_id')
                        ->whereHas('partnerChannel', function ($query) {
                            $query->whereHas('partner', function ($query) {
                                $query->whereHas('site', function ($query) {
                                    $query->makingRequest();
                                })->where('code', PredefinedPartnersEnum::LetsDoThis->value);
                            });
                        })->has('eventCategories');
                },
                'address',
                'image',
                'gallery'
            ])
            ->without(['eventCategories', 'eventThirdParties'])
            ->partnerEvent(Event::ACTIVE)
            ->where('status', Event::ACTIVE)
            ->where('slug','!=','gift-voucher');

         // Apply Filters based on Request
         $events->when($request->filled('region'), fn ($query) => $query->whereHas('region', function ($query) use ($request) {
            $query->where('ref', $request->region);
            }))
            ->when($request->filled('city'), fn ($query) => $query->whereHas('city', function ($query) use ($request) {
                $query->where('ref', $request->city);
            }))
            ->when($request->filled('venue'), fn ($query) => $query->whereHas('venue', function ($query) use ($request) {
                $query->where('ref', $request->venue);
            }));

        // Additional Filters for Event Categories
        $events->whereHas('eventCategories', function ($query) use ($request, $months) {
            $query->whereHas('site', function ($q1) use ($request) {
                $q1->where(function ($q2) use ($request) {
                    if ($request->filled('virtual_events') && $request->virtual_events == 'only') {
                        // Handle the virtual events only case
                    } else {
                        $q2->makingRequest();
                    }

                    if ($request->filled('virtual_events') && $request->virtual_events == 'include') {
                        // Handle the include virtual events case
                    }
                });
            });

            if ($request->filled('category')) {
                $query->where('event_categories.ref', $request->category);
            }

            if ($request->filled('start_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : null;

                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate ?? $startDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate ?? $startDate]);
                });
            }

            if ($request->filled('price')) {
                $price = $request->price;

                $query->where(function ($q) use ($price) {
                    $q->where('local_fee', '>=', $price[0])
                        ->where('local_fee', '<=', $price[1]);
                });
            }

            // Date Filtering by Month or Range
            if ($request->filled('date')) {
                $currentYear = Carbon::now()->year;

                switch (true) {
                    case in_array($request->date, $months):
                        $monthNumber = str_pad(array_search($request->date, $months) + 1, 2, '0', STR_PAD_LEFT);
                        $query->whereYear('start_date', $currentYear)
                            ->whereMonth('start_date', $monthNumber)
                            ->orWhere(function ($q) use ($monthNumber) {
                                $q->whereMonth('start_date', $monthNumber)
                                    ->where('start_date', '>=', now());
                            });
                        break;

                    case $request->date === 'this_year':
                        $query->whereYear('start_date', $currentYear);
                        break;

                    case $request->date === 'next_year':
                        $query->whereYear('start_date', Carbon::now()->addYear()->year);
                        break;

                    case $request->date === 'next_3_months':
                        $query->whereBetween('start_date', [now(), now()->addMonths(3)]);
                        break;

                    case $request->date === 'next_6_months':
                        $query->whereBetween('start_date', [now(), now()->addMonths(6)]);
                        break;
                }
            }
        });

        // Search by Name or Venue
        if ($request->filled('name')) {
            $events->where('status', Event::ACTIVE)
                ->where(function ($query) use ($request) {
                    $query->where('events.name', 'like', '%' . $request->name . '%')
                        ->orWhereHas('venue', function ($q) use ($request) {
                            $q->where('venues.name', 'like', '%' . $request->name . '%');
                        });
                });
        }

        // Location-based Filtering
        if ($request->filled('location')) {
            $location = $request->location;

            $events->whereHas('address', function ($query) use ($request, $location) {
                $query->withinRadius($location['latitude'], $location['longitude'], $request->radius);
            });
        }

        // Sorting Logic
        $events = $events->when($request->filled('date'), function ($query) use ($request) {
            if ($request->date == 'newest') {
                $query->latest();
            } elseif ($request->date == 'oldest') {
                $query->oldest();
            } else {
                $query->orderByRaw("
                CASE
                    WHEN (SELECT start_date FROM event_event_category WHERE event_event_category.event_id = events.id ORDER BY start_date LIMIT 1) >= CURDATE()
                    THEN 0
                    ELSE 1
                END
            ")->orderBy(
                    EventEventCategory::select('start_date')
                        ->whereColumn('event_id', 'events.id')
                        ->orderBy('start_date')
                        ->limit(1),
                    'asc'
                );
            }
        });

        $mapData = $events->get();
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
     * The events calendar.
     * Gets events for the requested month alongside those for the previous and the next month.
     *
     * @queryParam month_year string Filter by month_year. Example: 11-2022
     * @queryParam name string Filter by name. The name to search for. No-example
     * @queryParam category string Filter by event category ref. No-example
     * @queryParam region string Filter by region ref. No-example
     * @queryParam city string Filter by city ref. No-example
     * @queryParam venue string Filter by venue ref. No-example
     * @queryParam experience string Filter by experience ref. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param EventClientCalendarQueryParamsRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function calendar(EventClientCalendarQueryParamsRequest $request): JsonResponse
    {
        try {
            $events = (new CacheDataManager(
                $this->eventService,
                'calendar',
                [$request]
            ))->getData();
        } catch (ModelNotFoundException $e) {
            return $this->error('The events were not found!', 404);
        }

        return $this->success('The events calendar', 200, [
            'periods' => $this->distinctEventCalendarPeriods($request),
            'events' => new EventResource($events),
            'query_params' => $request->all()
        ]);
    }

    /**
     * The most popular events.
     *
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * @queryParam image_versions array The image versions to return. Example: card, tablet, desktop, mobile
     *
     * @param  PaginationRequest  $request
     * @return JsonResponse
     */
    public function popular(PaginationRequest $request): JsonResponse
    {
        try {
            $events = (new CacheDataManager(
                $this->eventService,
                'popular',
                [$request]
            ))->getData();
        } catch (ModelNotFoundException $e) {
            return $this->error('The events were not found!', 404);
        }

        return $this->success('The list of popular events', 200, new EventResource($events));
    }

    /**
     * The upcoming events.
     * @queryParam date string Filter by date. Must be one of newest, oldest, this_year, next_year, next_3_months, next_6_months, 2022-09, 2022-10. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param  Request       $request
     * @return JsonResponse
     */
    public function upcoming(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['nullable', 'string'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $events = (new CacheDataManager(
                $this->eventService,
                'upcoming',
                [$request]
            ))->getData();
        } catch (ModelNotFoundException $e) {
            return $this->error('The events were not found!', 404);
        }

        if( count($events) > 0 ) {
            return $this->success('The list of upcoming events', 200, [
                'events' => new EventResource($events)
            ]);
        }else {
            return $this->success('The events were not found!', 200);
        }
    }

    /**
     * The next events.
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param  PaginationRequest  $request
     * @return JsonResponse
     */
    public function next(PaginationRequest $request): JsonResponse
    {
        try {
            $events = (new CacheDataManager(
                $this->eventService,
                'next',
                [$request]
            ))->getData();
        } catch (ModelNotFoundException $e) {
            return $this->error('The events were not found!', 404);
        }

        return $this->success('The list of next events', 200, [
            'events' => new EventResource($events)
        ]);
    }

    /**
     * Get an event's details.
     *
     * @header X-Platform-User-Identifier-Key RTHUB.v1.98591b54-db61-46d4-9d29-47a8a7f325a8.1675084780
     * @urlParam event_slug string required The slug of the event. Example: lee-valley-velopark-10-mile-september
     *
     * @param  string       $event
     * @return JsonResponse
     */
    public function show(string $event): JsonResponse
    {
        try {
            Log::info("Fetching event details for event: {$event}");
            $_event = (new CacheDataManager(
                $this->eventService,
                'show',
                [$event]
            ))->getData();

            $from_date = Carbon::now()->subDays(30)->startOfDay();
            $to_date = Carbon::now()->endOfDay();
            $last_week_update = DB::table('participants')
                ->join('event_event_category', 'participants.event_event_category_id', '=', 'event_event_category.id')
                ->join('event_categories', 'event_categories.id', '=', 'event_event_category.event_category_id')
                ->where('event_categories.site_id', clientSiteId())
                ->where('event_event_category.event_id', $_event->id)
                ->whereBetween('participants.created_at', [$from_date, $to_date])
                ->count();
            $_event['last_week_update'] = $last_week_update;
            if ($_event->start_date_format)
            {
                $carbonDate = Carbon::createFromFormat('Y-m-d', $_event->start_date_format);
                $_event['month_year_format'] = $carbonDate->format('F j, Y');
                $_event['day_month_year_format'] = $carbonDate->format('l, jS F, Y');
            }

            AnalyticsViewEvent::dispatch($_event);

            return $this->success('The event details', 200, new EventResource($_event));
        } catch (ModelNotFoundException $e) {
            $origin = request()->headers->get('origin') ?? '';
            return (new RedirectManager(Event::class, $event, 'slug', $origin))->redirect();
        } catch (\Exception $e) {
            Log::error("General exception in event fetching: " . $e->getMessage(), ['exception' => $e]);
            Log::error($e);
            return $this->error('An error occurred while getting event details. '. $e->getMessage(), 500);
        }
    }

    /**
     * Checkout on Lets Do This
     *
     * @param  CheckoutOnLDTRequest $request
     * @return JsonResponse
     */
    public function checkoutOnLDT(CheckoutOnLDTRequest $request): JsonResponse
    {
        foreach ($request->ecetps as $ecetp) {
            $_ecetp = EventCategoryEventThirdParty::where('ref', $ecetp['ref'])->first();
            Enquiry::updateOrCreate(
                [
                    'email' => $request->email,
                    'site_id' => static::getSite()?->id,
                    'event_id' => $_ecetp->eventThirdParty->event_id,
                    'event_category_id' => $_ecetp->event_category_id,
                ], [
                    'updated_at' => Carbon::now(),
                    ...$request->all()
                ]
            );
        }

        $ecetps = EventCategoryEventThirdParty::with(['eventThirdParty.event', 'eventCategory'])
            ->whereIn('ref', collect($request->ecetps)->pluck('ref')->all())
            ->whereHas('eventCategory', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                });
            })->whereHas('eventThirdParty', function ($query) {
                $query->whereNotNull('external_id')
                    ->whereHas('partnerChannel', function ($query) {
                        $query->whereHas('partner', function ($query) {
                            $query->whereHas('site', function ($query) {
                                $query->makingRequest();
                            })->where('code', PredefinedPartnersEnum::LetsDoThis->value);
                        });
                    });
            })->get();

        try {
            if ($ecetps->count()) {
                $races = $ecetps->map(function ($ecetp) use ($request) {
                    $_ecetp = collect($request->ecetps)->firstWhere('ref', $ecetp['ref']);
                    $quantity = $_ecetp['quantity'] ?? 0;

                    return implode(",", array_fill(0, $quantity, $ecetp->external_id));
                })->toArray();

                $races = implode(",", $races);
                $site_id = $ecetps->pluck('eventCategory.site.id')->unique();

                $checkoutUrl = Event::checkoutOnLDT($races, $ecetps[0]->eventThirdParty->external_id,$site_id[0]);

                if (! $checkoutUrl)
                    throw new \Exception('The Lets Do This equivalence for this event was not found!');
            } else {
                throw new ModelNotFoundException();
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The '. static::singularOrPlural(['event was', 'events were'], $request->ecetps) .' not found!', 404);
            // TODO: Log this to the developers channel as it should never occur unless there is a logic error somewhere
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 406, $e->getMessage());
        }

        return $this->success('Checkout on LDT!', 200, $checkoutUrl);
    }

    /**
     * Get distinct periods for the event calendar
     *
     * @param  Request  $request
     * @return array
     */
    private function distinctEventCalendarPeriods(Request $request): array
    {
        $prevMonth = Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString();

        $eec = EventEventCategory::select('start_date', 'end_date')
            ->whereHas('eventCategory', function ($query) use ($request) {
                $query->whereHas('site', function ($query) use ($request) {
                    $query->makingRequest();
                });

                if ($request->filled('category')) {
                    $query->where('ref', $request->category);
                }
        });

        $eec = $eec->whereHas('event', function ($query) use ($request) {
            $query->estimated(Event::INACTIVE)
                ->archived(Event::INACTIVE)
                ->partnerEvent(Event::ACTIVE)
                ->where('status', Event::ACTIVE);

            if ($request->filled('name')) {
                $query = $query->where('name', 'like', '%'.$request->name.'%');
            }

            if ($request->filled('region')) {
                $query = $query->whereHas('region', function ($query) use ($request) {
                    $query->where('ref', $request->region);
                });
            }
        })->whereDate('end_date', '>=', $prevMonth)
        ->orderBy('start_date')
        ->get();

        $dates = $eec->unique(function($item) {
                return $item['start_date']->month. ' '.$item['start_date']->year;
            })->values()->map(function($item){
                return [
                    'value' => $item['start_date']->format('m').'-'.$item['start_date']->year,
                    'label' => $item['start_date']->format('F'). ' '.$item['start_date']->year
                ];
           })->toArray();

        return $dates;
    }
}
