<?php

namespace App\Services\DataServices;

use App\Http\Requests\ClientEventsSubListingQueryParamsRequest;
use App\Jobs\ProcessDataServiceExport;
use App\Models\Combination;
use App\Traits\Response;
use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use \Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Modules\Event\Requests\EventClientListingQueryParamsRequest;

use App\Enums\EventStateEnum;
use App\Enums\PredefinedPartnersEnum;
use App\Enums\EventCategoryVisibilityEnum;
use App\Models\City;
use App\Models\Region;
use App\Models\Venue;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventEventCategory;
use App\Services\ExportManager\FileExporterService;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Modules\Event\Requests\EventClientCalendarQueryParamsRequest;
use App\Services\EventListingService;
use App\Services\ExportManager\Formatters\EventExportableDataFormatter;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Traits\CustomPaginationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventClientDataService extends DataService implements DataServiceInterface
{
    use CustomPaginationTrait, Response;

    /**
     * @param  mixed  $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
        return $this->getFilteredEventsQuery($request);
    }

    /**
     * @param  mixed  $request
     * @return array|LengthAwarePaginator
     */
    public function getPaginatedList(mixed $request): array|LengthAwarePaginator
    {
        $query = $this->getFilteredQuery($request);

        return $this->siteBasedPagination($query, $request);
    }

    /**
     * @param  mixed  $request
     * @return Builder|Collection
     */
    public function getExportList(mixed $request): Builder|Collection
    {
        return $this->getFilteredQuery($request)->get();
    }

    /**
     * @param mixed $request
     * @return array|JsonResponse|BinaryFileResponse|StreamedResponse
     */
    public function downloadCsv(mixed $request): array|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        ProcessDataServiceExport::dispatch(
            (new FileExporterService(
                $this,
                new EventExportableDataFormatter,
                'events'
            )),
            json_encode($request),
            $request->user()
        );

        return $this->success('The exported file will be sent to your email shortly.');

//        return (new FileExporterService(
//            $this,
//            new EventExportableDataFormatter,
//            'events'
//        ))->download($request);
    }

    /**
     * @param  EventClientCalendarQueryParamsRequest  $request
     * @return mixed
     */
    public function calendar(EventClientCalendarQueryParamsRequest $request): mixed
    {
        return $this->getFilteredEventsCalendarQuery($request)->get()->groupBy([function ($item, $key) {
            return Carbon::parse($item->eventCategories[0]->pivot->start_date)->format('m/Y');
        }, function ($item, $key) {
            return Carbon::parse($item->eventCategories[0]->pivot->start_date)->format('d/m/Y');
        }]);
    }

    /**
     * @param  Request              $request
     * @return LengthAwarePaginator
     */
    public function popular(Request $request): LengthAwarePaginator
    {
        $query = $this->getFilteredPopularEventsQuery($request);

        return $this->paginate($query);
    }

    /**
     * @param  Request              $request
     * @return LengthAwarePaginator
     */
    public function upcoming(Request $request): LengthAwarePaginator
    {
        $query = $this->getFilteredUpcomingEventsQuery($request);

        return $this->paginate($query);
    }

    /**
     * @param  Request              $request
     * @return LengthAwarePaginator
     */
    public function next(Request $request): LengthAwarePaginator
    {
        $query = $this->getFilteredNextEventsQuery($request);

        return $this->paginate($query);
    }

    /**
     * @param  string  $event
     * @return Event
     */
    public function show(string $event): Event
    {
        $_event = $this->getFilteredShowQuery($event)
        ->when(request()->draft, fn ($query) => $query->onlyDrafted())
        ->firstOrFail();

        if ($event) {
            $_event['latest_tweet'] = Event::latestTweet($_event); // Get the latest tweet for the event
            $_event['media'] = $_event->media(); // Get the event media

            if (is_array($_event['date_range'])) {
                $startDateTime = Carbon::parse($_event['date_range'][0]);
                $startFormattedDate = $startDateTime->format('Y-m-d');
                $startFormattedTime = $startDateTime->format('H:i');
                $endDateTime = Carbon::parse($_event['date_range'][1]);
                $endFormattedDate = $endDateTime->format('Y-m-d');
                $endFormattedTime = $endDateTime->format('H:i');
            } else if (is_object($_event['date_range'])) {
                $startDateTime = Carbon::parse($_event['date_range']);
                $startFormattedDate = $startDateTime->format('Y-m-d');
                $startFormattedTime = $startDateTime->format('H:i');
                $endDateTime = Carbon::parse($_event['date_range']);
                $endFormattedDate = $endDateTime->format('Y-m-d');
                $endFormattedTime = $endDateTime->format('H:i');
            }
            $_event['start_date_format'] = $startFormattedDate;
            $_event['start_time_format'] = $startFormattedTime;
            $_event['end_date_format'] = $endFormattedDate;
            $_event['end_time_format'] = $endFormattedTime;

            return $_event;
        } else {
            throw new ModelNotFoundException();
        }
    }

    /**
     * @param  EventClientListingQueryParamsRequest  $request
     * @return Builder
     */
    private function getFilteredEventsQuery(EventClientListingQueryParamsRequest $request): Builder
    {
        $events = Event::select('id', 'ref', 'name', 'slug', 'registration_method','status')
            ->with(['eventCategories:id,ref,name', 'eventCategories' => function ($query) {
                $query->withoutAppends();
            }, 'eventThirdParties:id,ref,event_id,external_id,partner_channel_id', 'eventThirdParties' => function ($query) {
                $query->with(['eventCategories:id,ref,name', 'eventCategories' => function ($query) {
                    $query->withoutAppends();
                }, 'partnerChannel:id,partner_id,ref,name', 'partnerChannel.partner:id,ref,name,code'])
                ->whereNotNull('external_id')
                    ->whereHas('partnerChannel', function ($query) {
                        $query->whereHas('partner', function ($query) {
                            $query->whereHas('site', function ($query) {
                                $query->makingRequest();
                            })->where('code', PredefinedPartnersEnum::LetsDoThis->value);
                        });
                    })->has('eventCategories');
            }, 'address', 'image', 'gallery'])
            ->appendsOnly([
                'local_registration_fee_range',
                'international_registration_fee_range',
                'date_range',
                'state',
                'registration_deadline_range',
                'website_registration_method'
            ])
          //  ->state(EventStateEnum::Live)
            ->partnerEvent(Event::ACTIVE)
            ->where('status', Event::ACTIVE)
            ->where('slug','!=','gift-voucher')
            ->when($request->filled('region'), function ($query) use ($request) {
                $query->whereHas('regions_relationship', function ($subQuery) use ($request) {
                    $subQuery->where('regions.ref', $request->region);
                });
            })
            // ->when($request->filled('region'), fn ($query) => $query->whereHas('region', function ($query) use ($request) {
            //     $query->where('ref', $request->region);
            // }))
            ->when($request->filled('city'), fn ($query) => $query->whereHas('city', function ($query) use ($request) {
                $query->where('ref', $request->city);
            }))
            ->when($request->filled('venue'), fn ($query) => $query->whereHas('venue', function ($query) use ($request) {
                $query->where('ref', $request->venue);
            }));

        $events = $events->whereHas('eventCategories', function ($query) use ($request) {
            $query->whereHas('site', function ($q1) use ($request) {
                $q1->where(function ($q2) use ($request) {

                    if ($request->filled('virtual_events') && $request->virtual_events == 'only') {
                        // TODO: Handle this case
                    } else {
                        $q2->makingRequest();
                    }

                    if ($request->filled('virtual_events')) {
                        if ($request->virtual_events == 'include') {
                            // TODO: Handle this case
                        }
                    }
                });
            });

            if ($request->filled('category')) {
                $query->where('event_categories.ref', $request->category);
            }

            $query->when(
                $request->filled('start_date') && !$request->filled('end_date'), // When range is not available
                fn ($query) => $query->whereDate('start_date', Carbon::parse($request->start_date))
            )->when(
                $request->filled('start_date') && $request->filled('end_date'), // When range is available
                fn ($query) => $query->where(function ($query) use ($request) {
                    $startDate = Carbon::parse($request->start_date)->startOfDay(); // Ensure start date starts at 00:00:00
                    $endDate = Carbon::parse($request->end_date)->endOfDay(); // Ensure end date ends at 23:59:59
                    
                    $query->whereBetween('start_date', [$startDate,$endDate])
                    ->orWhereBetween('end_date', [$startDate,$endDate]);
                })
            );

            if ($request->filled('price')) {
                $price = $request->price;

                $query->where(function ($query) use ($price) {
                    $query->where('local_fee', '>=', $price[0]);
                    $query->where('local_fee', '<=', $price[1]);
                });
            }

            $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

            if ($request->filled('date')) {

                if (in_array($request->date, $months)) {
                    $monthNumber = array_search($request->date, $months) + 1; // Get the month number (1-12)
                    // Pad month number to 2 digits (01, 02, 03, etc.)
                    $formattedMonth = str_pad($monthNumber, 2, '0', STR_PAD_LEFT);
                    $currentYear = Carbon::now()->year;
                    $query->where('start_date', 'like', "$currentYear-$formattedMonth-%")
                        ->orWhere(function ($query) use ($formattedMonth) {
                            $query->where('start_date', 'like', "%-$formattedMonth-%")
                                ->where('start_date', '>=', now());
                        });

                        $query->where('event_categories.site_id', clientSiteId());
                        if ($request->filled('category')) {
                            $query->where('event_categories.ref', $request->category);
                        }
                }

                if ($request->date == 'this_year') {
                    $query->whereYear('start_date', Carbon::now()->year);
                }

                if ($request->date == 'next_year') {
                    $query->whereYear('start_date', Carbon::now()->addYear()->year);
                }

                if ($request->date == 'next_3_months') {
                    $query->whereBetween('start_date', [Carbon::now(), Carbon::now()->addMonths(3)]);
                }

                if ($request->date == 'next_6_months') {
                    $query->whereBetween('start_date', [Carbon::now(), Carbon::now()->addMonths(6)]);
                }

            }
        });

        if ($request->filled('name')) {
            $events = $events->where('status', Event::ACTIVE)->where(function ($query) use ($request) {
                $query->where('events.name', 'like', '%' . $request->name . '%')
                    ->orWhereHas('venue', function ($query) use ($request) {
                    $query->where('venues.name', 'like', '%' . $request->name . '%');
                });
            });
        }

        if ($request->filled('location')) {
            $location = $request->location;

            $events->whereHas('address', function ($query) use ($request, $location) {
                $query->withinRadius($location['latitude'], $location['longitude'], $request->radius);
            });
        }

        if ($request->filled('date') && $request->date == 'newest') {
            $events = $events->latest();
        } else if ($request->filled('date') && $request->date == 'oldest') {
            $events = $events->oldest();
        } else {
            $events = $events->orderByRaw("CASE WHEN (SELECT start_date FROM event_event_category WHERE event_event_category.event_id = events.id ORDER BY start_date
            LIMIT 1) >= CURDATE() THEN 0 ELSE 1 END")->orderBy(
              EventEventCategory::select('start_date')
                  ->whereColumn('event_id', 'events.id')
                  ->orderBy('start_date')
                  ->limit(1),
              'asc'
          );
        }

        return $events;
    }

    /**
     * @param  EventClientCalendarQueryParamsRequest  $request
     * @return Builder
     */
    private function getFilteredEventsCalendarQuery(EventClientCalendarQueryParamsRequest $request): Builder
    {
        $request['month_year'] = '01-' . $request->month_year;

        $nextMonth = Carbon::parse($request->month_year)->addMonthNoOverflow()->endOfMonth();
        $prevMonth = Carbon::parse($request->month_year)->subMonthNoOverflow()->startOfMonth();

        $events = Event::select('id', 'ref', 'name', 'slug', 'registration_method')
            ->with(['eventCategories:id,ref,name', 'eventCategories' => function ($query) use ($request, $prevMonth, $nextMonth) {
                $query->withoutAppends()
                    ->whereHas('site', function ($query) {
                        $query->makingRequest();
                    })->orderBy('start_date')
                    ->when(
                        $request->filled('category'),
                        fn ($query) => $query->where('event_categories.ref', $request->category)
                    )->when(
                        $request->filled('month_year'),
                        fn ($query) => $query->whereBetween('end_date', [$prevMonth, $nextMonth])
                    );
            }, 'image', 'gallery'])
            ->appendsOnly([
                'local_registration_fee_range',
                'international_registration_fee_range',
                'date_range',
                'registration_deadline_range',
                'website_registration_method'
            ])->partnerEvent(Event::ACTIVE)
            ->estimated(Event::INACTIVE)
            ->archived(Event::INACTIVE)
            ->where('status', Event::ACTIVE);

        $events = $events->whereHas('eventCategories', function ($query) use ($request, $prevMonth, $nextMonth) {
            $query->whereHas('site', function ($query) {
                $query->makingRequest();
            })->when(
                $request->filled('category'),
                fn ($query) => $query->where('event_categories.ref', $request->category)
            )->when(
                $request->filled('month_year'),
                fn ($query) => $query->whereBetween('end_date', [$prevMonth, $nextMonth])
            );
        });

        $events = $events->when(
            $request->filled('name'),
            fn ($query) => $query->where('name', 'like', '%' . $request->name . '%')
        )->when(
            $request->filled('region'),
            fn ($query) => $query->whereHas('region', function ($query) use ($request) {
                $query->where('ref', $request->region);
            })
        )->when(
            $request->filled('city'),
            fn ($query) => $query->whereHas('city', function ($query) use ($request) {
                $query->where('ref', $request->city);
            })
        )->when(
            $request->filled('venue'),
            fn ($query) => $query->whereHas('venue', function ($query) use ($request) {
                $query->where('ref', $request->venue);
            })
        )->when(
            $request->filled('experience'),
            fn ($query) => $query->whereHas('experiences', function ($query) use ($request) {
                $query->where('experiences.ref', $request->experience);
            })
        );

        return $events = $events->orderBy(
            EventEventCategory::select('start_date')
                ->whereColumn('event_id', 'events.id')
                ->orderBy('start_date')
                ->limit(1)
        );
    }

    /**
     * @param  Request  $request
     * @return Builder
     */
    private function getFilteredPopularEventsQuery(Request $request): Builder
    {
        $events = Event::select('id', 'ref', 'name', 'slug', 'registration_method')
            ->withCount('participants')
            ->appendsOnly([
                'local_registration_fee_range',
                'international_registration_fee_range',
                'date_range',
                'registration_deadline_range',
                'website_registration_method'
            ])->with(['eventCategories:id,ref,name', 'eventCategories' => function ($query) {
                $query->withoutAppends()
                    ->whereHas('site', function ($query) {
                        $query->makingRequest();
                    });
                $query->where('start_date', '>', Carbon::now());
            }, 'image', 'gallery'])
            ->state(EventStateEnum::Live)
            ->partnerEvent(Event::ACTIVE);

        $events = $events->where(function ($query) {
            $query->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                })->where('start_date', '>', Carbon::now());
            });
        });

        return $events->orderByDesc('participants_count');
    }

    /**
     * @param  Request  $request
     * @return Builder
     */
    private function getFilteredUpcomingEventsQuery(Request $request): Builder
    {
        $events = Event::select('id', 'ref', 'name', 'slug', 'registration_method')
            ->appendsOnly([
                'local_registration_fee_range',
                'international_registration_fee_range',
                'date_range',
                'registration_deadline_range',
                'website_registration_method'
            ])->with(['eventCategories:id,ref,name', 'eventCategories' => function ($query) use ($request) {
                $query->withoutAppends()
                    ->whereHas('site', function ($query) {
                        $query->makingRequest();
                    });

                if ($request->filled('date')) {
                    if ($request->date == 'this_year') {
                        $query->whereYear('start_date', Carbon::now()->year);
                    }

                    if ($request->date == 'next_year') {
                        $query->whereYear('start_date', Carbon::now()->addYear()->year);
                    }

                    if ($request->date == 'next_3_months') {
                        $query->whereBetween('start_date', [Carbon::now(), Carbon::now()->addMonths(3)]);
                    }

                    if ($request->date == 'next_6_months') {
                        $query->whereBetween('start_date', [Carbon::now(), Carbon::now()->addMonths(6)]);
                    }

                    if (!in_array($request->date, ['this_year', 'next_year', 'next_3_months', 'next_6_months', 'newest', 'oldest']) && strtotime($request->date)) {
                        $query->whereBetween('start_date', [Carbon::parse($request->date)->startOfMonth()->format('Y-m-d'), Carbon::parse($request->date)->endOfMonth()->format('Y-m-d')]);
                    }
                }
            }, 'image', 'gallery'])
            ->state(EventStateEnum::Live)
            ->partnerEvent(Event::ACTIVE);

        $events = $events->whereHas('eventCategories', function ($query) use ($request) {
            $query->whereHas('site', function ($query) use ($request) {
                $query->makingRequest();

                $query->where('start_date', '>', Carbon::now());

                if ($request->filled('date')) {
                    if ($request->date == 'this_year') {
                        $query->whereYear('start_date', Carbon::now()->year);
                    }

                    if ($request->date == 'next_year') {
                        $query->whereYear('start_date', Carbon::now()->addYear()->year);
                    }

                    if ($request->date == 'next_3_months') {
                        $query->whereBetween('start_date', [Carbon::now(), Carbon::now()->addMonths(3)]);
                    }

                    if ($request->date == 'next_6_months') {
                        $query->whereBetween('start_date', [Carbon::now(), Carbon::now()->addMonths(6)]);
                    }

                    if (!in_array($request->date, ['this_year', 'next_year', 'next_3_months', 'next_6_months', 'newest', 'oldest']) && strtotime($request->date)) {
                        $query->whereBetween('start_date', [Carbon::parse($request->date)->startOfMonth()->format('Y-m-d'), Carbon::parse($request->date)->endOfMonth()->format('Y-m-d')]);
                    }
                }
            });
        });

        if ($request->filled('date') && $request->date == 'newest') {
            $events = $events->latest();
        } else if ($request->filled('date') && $request->date == 'oldest') {
            $events = $events->oldest();
        } else {
            $events = $events->orderBy(
                EventEventCategory::select('start_date')
                    ->whereColumn('event_id', 'events.id')
                    ->orderBy('start_date')
                    ->limit(1)
            );
        }

        return $events;
    }

    /**
     * @param  Request  $request
     * @return Builder
     */
    private function getFilteredNextEventsQuery(Request $request): Builder
    {
        // Get the closest upcoming/next event
        $closestEvent = $this->closestEvent();

        // Get all the events occuring on the same day as the closest event
        $events = Event::select('id', 'ref', 'name', 'slug', 'registration_method')
            ->with(['eventCategories:id,ref,name', 'eventCategories' => function ($query) {
                $query->whereHas('site', function ($query) {
                        $query->makingRequest();
                    })->orderBy('start_date');
            }, 'image', 'gallery'])->state(EventStateEnum::Live)
            ->partnerEvent(Event::ACTIVE);

        $events = $events->whereHas('eventCategories', function ($query) use ($closestEvent) {
            $query->whereHas('site', function ($query) {
                $query->makingRequest();
            });

            $query->where('start_date', '>', Carbon::now())
                ->whereDate('start_date', Carbon::parse($closestEvent?->eventCategories[0]?->pivot?->start_date)->toDateString());
        });

        return $events = $events->orderBy(
            EventEventCategory::select('start_date')
                ->whereColumn('event_id', 'events.id')
                ->orderBy('start_date')
                ->limit(1)
        );
    }

    /**
     * @param  string  $event
     * @return Builder
     */
    private function getFilteredShowQuery(string $event): Builder
    {
        $event = Event::select(['id', 'ref', 'name', 'slug', 'description', 'event_day_logistics', 'how_to_get_there', 'kit_list', 'show_address', 'route_info_code', 'route_info_description', 'spectator_info', 'video', 'what_is_included_description', 'website', 'registration_method', 'created_at', 'updated_at'])
            ->with(['eventCategories:id,ref,name,color,visibility', 'medals:id,medalable_id,medalable_type,name,description', 'medals.upload', 'eventThirdParties:id,ref,event_id,external_id,partner_channel_id', 'eventThirdParties' => function ($query) {
                $query->with(['eventCategories:id,ref,name', 'eventCategories' => function ($query) {
                    $query->withoutAppends();
                }, 'partnerChannel:id,partner_id,ref,name', 'partnerChannel.partner:id,ref,name,code'])
                    ->whereNotNull('external_id')
                    ->whereHas('partnerChannel', function ($query) {
                        $query->whereHas('partner', function ($query) {
                            $query->whereHas('site', function ($query) {
                                $query->makingRequest();
                            })->where('code', PredefinedPartnersEnum::LetsDoThis->value);
                        });
                    })->has('eventCategories');
            }, 'experiences', 'faqs:id,faqsable_id,faqsable_type,section,description', 'faqs.faqDetails:id,faq_id,question,answer,view_more_link', 'faqs.faqDetails.uploads', 'image', 'gallery', 'address:id,locationable_id,locationable_type,address,coordinates', 'meta:id,metable_id,metable_type,title,description,keywords,canonical_url,robots,created_at,updated_at', 'routeInfoMedia', 'socials:id,socialable_id,socialable_type,platform,url', 'whatIsIncludedMedia', 'socials'])
            ->where('slug', $event);

        return $event = $event->whereHas('eventCategories', function ($query) {
            $query->whereHas('site', function ($query) {
                $query->makingRequest();
            });
        })->partnerEvent(Event::ACTIVE);
    // })->state(EventStateEnum::Live)->partnerEvent(Event::ACTIVE);
    }

    /**
     * Get the closest next event
     *
     * @return ?Event
     */
    private function closestEvent(): ?Event
    {
        $closestEvent = Event::select('id', 'ref', 'registration_method')
            ->with(['eventCategories' => function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                })->orderBy('start_date');
            }])->state(EventStateEnum::Live)
            ->partnerEvent(Event::ACTIVE);

        $closestEvent = $closestEvent->whereHas('eventCategories', function ($query) {
            $query->whereHas('site', function ($query) {
                $query->makingRequest();
            });

            $query->where('start_date', '>', Carbon::now());
        });

        $closestEvent = $closestEvent->orderBy(
            EventEventCategory::select('start_date')
                ->whereColumn('event_id', 'events.id')
                ->orderBy('start_date')
                ->limit(1)
        )->first();

        return $closestEvent;
    }

    /**
     * Get events belonging to a given property (category, region, city, venue, combination)
     *
     * @param             $property
     * @param  Request    $request
     * @return Collection
     */
    public function getFilteredEventsByProperty($property, $request)
    {
        return (new EventListingService(
            $property->events()->getQuery(),
            $property instanceof EventCategory
                ? $request->all() // Include all parameters, including 'category'
                : $request->all()
        ))->getFilteredClientCollection();

        // return (new EventListingService(
        //     $property->events()->getQuery(),
        //     $property instanceof EventCategory
        //         ? $request->except(['category'])
        //         : $request->all()
        // ))->getFilteredClientCollection();
    }

    /**
     * @param Combination $combination
     * @param ClientEventsSubListingQueryParamsRequest $request
     * @return LengthAwarePaginator|array
     */
    public function getFilteredCombinationEvents(Combination $combination, ClientEventsSubListingQueryParamsRequest $request): LengthAwarePaginator|array
    {
        return (new EventListingService(
            Event::query()
               // ->state(EventStateEnum::Live)
                ->partnerEvent(Event::ACTIVE)
                ->where(function ($q1) use ($combination) {
                    $regionId = array_column(json_decode($combination->region_id, true) ?? [], 'id');
                    $cityId = array_column(json_decode($combination->city_id, true) ?? [], 'id');
                    $venueId = array_column(json_decode($combination->venue_id, true) ?? [], 'id');
                    $seriesId = array_column(json_decode($combination->series_id, true) ?? [], 'id');
                    $eventCategoryId = array_column(json_decode($combination->event_category_id, true) ?? [], 'id');
                    $month = !empty($combination->month) ? date('n', strtotime($combination->month)) : null;
                    $year = $combination->year;
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
                        })
                        ->when(!empty($month), function ($q2) use ($month) {
                            
                            $q2->whereHas('eventCategories', function ($q3) use ($month) {
                                $q3->whereMonth('start_date', '=', $month);
                            });
                        })
                        ->when(!empty($year), function ($q2) use ($year) {
                            $q2->whereHas('eventCategories', function ($q3) use ($year) {
                                $q3->whereYear('start_date', '=', $year);
                            });
                        });
                }),
            $request->all()
        ))->getFilteredClientCollection();
    }

    /**
     * Get the price range
     *
     * @param  ?Model    $property
     * @param  ?Request  $request
     * @return array
     */
    public function getPriceRange(?Model $property = null, ?Request $request = null): array
    {
        if ($request?->venue) {
            $request['venue'] = $request->venue;
        } else if ($property instanceof Venue) {
            $request['venue'] = $property->ref;
        }

        if ($request?->city) {
            $request['city'] = $request->city;
        } else if ($property instanceof City) {
            $request['city'] = $property->ref;
        }

        if ($request?->region) {
            $request['region'] = $request->region;
        } else if ($property instanceof Region) {
            $request['region'] = $property->ref;
        }

        if ($request?->category) {
            $request['category'] = $request->category;
        } else if ($property instanceof EventCategory) {
            $request['category'] = $property->ref;
        }

        $query = DB::table('event_event_category')
            ->whereIn('event_event_category.event_id', function ($query) use ($request) {
                $query->select('events.id')
                    ->from('events')
                    ->where('event_event_category.start_date', '>', Carbon::now())
                    ->where('events.partner_event', Event::ACTIVE)
                    ->where('events.status', Event::ACTIVE)
                    ->where('events.estimated', Event::INACTIVE)
                    ->where('events.archived', Event::INACTIVE)
                    ->when($request?->category, function ($query) use ($request) {
                        $query->where('event_event_category.event_category_id', function ($query) use ($request) {
                            $query->select('event_categories.id')
                                ->from('event_categories')
                                ->where('event_categories.ref', $request->category);
                        });
                    })->when($request?->region, function ($query) use ($request) {
                        $query->where('events.region_id', function ($query) use ($request) {
                            $query->select('regions.id')
                                ->from('regions')
                                ->where('regions.ref', $request->region);
                        });
                    })->when($request?->city, function ($query) use ($request) {
                        $query->where('events.city_id', function ($query) use ($request) {
                            $query->select('cities.id')
                                ->from('cities')
                                ->where('cities.ref', $request->cities);
                        });
                    })->when($request?->venue, function ($query) use ($request) {
                        $query->where('events.venue_id', function ($query) use ($request) {
                            $query->select('venues.id')
                                ->from('venues')
                                ->where('venues.ref', $request->venue);
                        });
                    });
            });

        $numbers = [1, 10, 100, 1000, 10000, 1000000, 10000000, 100000000, 1000000000, 10000000000];

        $min = $query->clone()
            ->min('local_fee');

        $charLength = strlen((int) $min);

        $min = floor($min / $numbers[$charLength > 1 ? $charLength - 1 : $charLength]) * $numbers[$charLength - 1]; // Round down to the nearest 10th, 100th, ....

        $max = $query->clone()
            ->max('local_fee');

        $charLength = strlen((int) $max);

        $max = ceil($max / $numbers[$charLength - 1]) * $numbers[$charLength - 1]; // Round up to the nearest 10th, 100th, ...

        return $min == 0 && $max == 0
            ? [0, 100] // The default range
            : [$min, $max];
    }
}
