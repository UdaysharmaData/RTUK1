<?php

namespace App\Services;

use Carbon\Carbon;
use App\Enums\EventStateEnum;
use App\Modules\Event\Models\Event;
use App\Traits\CustomPaginationTrait;
use Illuminate\Database\Query\Builder;
use App\Enums\PredefinedPartnersEnum;
use App\Modules\Event\Models\EventEventCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EventListingService
{
    use CustomPaginationTrait;

    /**
     * @var mixed|null
     */
    readonly private mixed $category;
    /**
     * @var mixed|null
     */
    readonly private mixed $start_date;
    /**
     * @var mixed|null
     */
    readonly private mixed $end_date;
    /**
     * @var mixed|null
     */
    readonly private mixed $price;
    /**
     * @var mixed|null
     */
    readonly private mixed $date;
    /**
     * @var mixed|null
     */
    readonly private mixed $name;
    /**
     * @var mixed|null
     */
    readonly private mixed $region;
    /**
     * @var mixed|null
     */
    readonly private mixed $city;
    /**
     * @var mixed|null
     */
    readonly private mixed $venue;
    /**
     * @var mixed|null
     */
    readonly private mixed $address;
    /**
     * @var int|mixed
     */
    readonly private mixed $perPage;

    readonly private bool $drafted;

    /**
     * @var bool
     */
    protected bool $appendAnalyticsData = false;

    public function __construct(
        protected Builder|\Illuminate\Database\Eloquent\Builder $eventQuery,
        protected array $filters = []
    ){
        $this->category = $this->filters['category'] ?? null;
        $this->start_date = $this->filters['start_date'] ?? null;
        $this->end_date = $this->filters['end_date'] ?? null;
        $this->date = $this->filters['date'] ?? null;
        $this->price = $this->filters['price'] ?? null;
        $this->name = $this->filters['name'] ?? null;
        $this->region = $this->filters['region'] ?? null;
        $this->address = $this->filters['address'] ?? null;
        $this->city = $this->filters['city'] ?? null;
        $this->venue = $this->filters['venue'] ?? null;
        $this->perPage = $this->filters['per_page'] ?? 10;
    
        $this->appendAnalyticsData = false;
    }

    /**
     * @return array
     */
    public function getFilteredClientCollection(): array|LengthAwarePaginator
    {
        $query = Event::withOnly(['eventCategories' => function ($q) {
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
           // ->state(EventStateEnum::Live)
            ->partnerEvent(Event::ACTIVE)
            ->where('status', Event::ACTIVE)
            ->when($this->name, fn ($query) => $query->where('name', 'like', '%' . $this->name . '%')->where('status', Event::ACTIVE))
            // ->when($this->region, fn ($query) => $query->whereHas('region', function ($query) {
            //     $query->where('ref', $this->region);
            // }))
            ->when($this->region, fn ($query) => $query->whereHas('regions_relationship', function ($query) {
                $query->where('regions.ref', $this->region);
            }))
            ->when($this->city, fn ($query) => $query->whereHas('cities_relationship', function ($query) {
                $query->where('cities.ref', $this->city);
            }))
            ->when($this->venue, fn ($query) => $query->whereHas('venues_relationship', function ($query) {
                $query->where('venues.ref', $this->venue);
            }))
            ->when($this->category, fn ($query) => $query->whereHas('eventCategories', function ($query) {
                $query->where('event_categories.ref', $this->category);
            }))
            ->when($this->address, fn ($query) => $query->whereHas('address', function ($query) {
                $query->where('address', 'like', '%' . $this->address . '%');
            }))
            ->when($this->category || $this->start_date || $this->end_date || $this->date || $this->price, fn ($query) => $query->whereHas('eventCategories', function ($query) {
                $query->when($this->category, fn ($query) => $query->where('event_categories.ref', $this->category))
                    ->when($this->price, fn ($query) => $query->whereBetween('local_fee', $this->price))
                    ->when(
                        $this->start_date && !$this->end_date,
                        fn ($query) => $query->whereDate('start_date', Carbon::parse($this->start_date))
                    )->when(
                        $this->start_date && $this->end_date,
                        fn ($query) => $query->whereDate('start_date', '>=', Carbon::parse($this->start_date))
                            ->whereDate('end_date', '<=', Carbon::parse($this->end_date))
                    );
                $query->when(
                    $this->date,
                    fn ($query) => $query->when($this->date == 'this_year', fn ($query) => $query->whereYear('start_date', Carbon::now()->year))
                        ->when($this->date == 'next_year', fn ($query) => $query->whereYear('start_date', Carbon::now()->addYear()->year))
                        ->when($this->date == 'next_3_months', fn ($query) => $query->whereBetween('start_date', [Carbon::now(), Carbon::now()->addMonths(3)]))
                        ->when($this->date == 'next_6_months', fn ($query) => $query->whereBetween('start_date', [Carbon::now(), Carbon::now()->addMonths(6)]))
                        ->when(!in_array($this->date, ['this_year', 'next_year', 'next_3_months', 'next_6_months', 'newest', 'oldest']), fn ($query) => $query->whereBetween('start_date', [Carbon::parse($this->date)->startOfMonth()->format('Y-m-d'), Carbon::parse($this->date)->endOfMonth()->format('Y-m-d')]))
                );
            }))
            ->when($this->date && $this->date == 'newest', fn ($query) => $query->latest())
            ->when($this->date && $this->date == 'oldest', fn ($query) => $query->oldest());

            $query = $query->orderByRaw("CASE WHEN (SELECT start_date FROM event_event_category WHERE event_event_category.event_id = events.id ORDER BY start_date
            LIMIT 1) >= CURDATE() THEN 0 ELSE 1 END")->orderBy(
              EventEventCategory::select('start_date')
                  ->whereColumn('event_id', 'events.id')
                  ->orderBy('start_date')
                  ->limit(1),
              'asc'
          );
          $query = $query->select('events.id', 'events.ref', 'events.name', 'events.slug', 'events.registration_method','events.status');

            // orderByDesc(
            //     EventEventCategory::select('start_date')
            //         ->whereColumn('event_id', 'events.id')
            //         ->orderBy('start_date')
            //         ->limit(1)
            // )
            

        return $this->siteBasedPagination($query, request());
    }
}
