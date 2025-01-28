<?php

namespace App\Services\DataServices;

use App\Enums\EventStateEnum;
use App\Filters\CombinationOrderByFilter;
use App\Filters\DeletedFilter;
use App\Filters\DraftedFilter;
use App\Filters\FaqsFilter;
use App\Filters\MetaKeywordsFilter;
use App\Filters\PeriodFilter;
use App\Filters\YearFilter;
use App\Models\Combination;
use App\Services\Reporting\CombinationStatistics;
use App\Services\DataServices\Contracts\DataServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CombinationDataService extends DataService implements DataServiceInterface
{
    /**
     * @var bool
     */
    private bool $loadRedirect = false;

    public function __construct()
    {
        $this->builder = Combination::query();
        $this->appendAnalyticsData = false;
    }

    /**
     * @param bool $value
     * @return CombinationDataService
     */
    public function setLoadRedirect(bool $value): static
    {
        $this->loadRedirect = $value;

        return $this;
    }

    /**
     * @param mixed $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
        $term = request('term');
        $category = request('category');
        $region = request('region');
        $venue = request('venue');
        $city = request('city');
        $country = request('country');
        $parameters = array_filter(request()->query());
        $query = $this->builder
            ->when($this->loadRedirect, fn($query) => $query->with('redirect'))
            ->without(['faqs', 'meta', 'events', 'gallery']);

        $query->filterListBy(new DeletedFilter)
            ->filterListBy(new DraftedFilter)
            ->filterListBy(new CombinationOrderByFilter)
            ->filterListBy(new PeriodFilter)
            ->filterListBy(new YearFilter)
            ->filterListBy(new FaqsFilter)
            ->filterListBy(new MetaKeywordsFilter);

        if (count($parameters) === 0) {
            $query = $query->latest();
        }

        return $query->when($term, $this->applySearchTermFilter($term));
            // ->when($category, fn($query) => $query->whereHas('eventCategory', fn(Builder $query) => $query->where('event_categories.ref', $category)))
            // ->when($city, fn($query) => $query->whereHas('city', fn(Builder $query) => $query->where('ref', $city)))
            // ->when($region, fn($query) => $query->whereHas('region', fn(Builder $query) => $query->where('ref', $region)))
            // ->when($country, fn($query) => $query->whereHas('region', fn(Builder $query) => $query->where('country', $country)))
            // ->when($venue, fn($query) => $query->whereHas('venue', fn(Builder $query) => $query->where('ref', $venue)));
    }

    /**
     * @param string|null $term
     * @return \Closure
     */
    private function applySearchTermFilter(string|null $term): \Closure
    {
        return function (Builder $query) use ($term) {
            if (isset($term)) {
                $query->where('name', 'LIKE', "%$term%")
                    ->orWhere('description', 'LIKE', "%$term%")
                    ->orWhereHas('meta', function (Builder $query) use($term) {
                        $query->where('title', 'LIKE', "%$term%")
                            ->orWhere('description', 'LIKE', "%$term%")
                            ->orWhereJsonContains('keywords', $term);
                    })
                    ->orWhereHas('region', function (Builder $query) use($term) {
                        $query->where('name', 'LIKE', "%$term%")
                            ->orWhere('description', 'LIKE', "%$term%");
                    })
                    ->orWhereHas('city', function (Builder $query) use($term) {
                        $query->where('name', 'LIKE', "%$term%")
                            ->orWhere('description', 'LIKE', "%$term%");
                    })
                    ->orWhereHas('venue', function (Builder $query) use($term) {
                        $query->where('name', 'LIKE', "%$term%")
                            ->orWhere('description', 'LIKE', "%$term%");
                    })
                    ->orWhereHas('eventCategory', function (Builder $query) use($term) {
                        $query->where('name', 'LIKE', "%$term%");
                    });
            }
        };
    }

    /**
     * @param mixed $request
     * @return LengthAwarePaginator
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator
    {
        $data = $this->paginate($this->getBuilderWithAnalytics($this->getFilteredQuery($request)))->through(function ($combination) {
            $combination->append('draft_url');

            return $combination;
        });

        return $data;
    }

    /**
     * @return LengthAwarePaginator
     */
    public function _index(): LengthAwarePaginator
    {
        $term = request('term');
        $popular = request('popular');
        $query = Combination::query()
            ->without(['region', 'city', 'venue', 'faqs', 'meta', 'events', 'eventCategory'])
            ->withCount(['events as active_events_count' => function ($query) {
                $query->state(EventStateEnum::Live);
            }]);

        $combinations = $query
            ->when($term, fn($query) => $query->where('name', 'LIKE', "%$term%"))
            ->when(
                $popular === 'true',
                fn($query) =>  $query->orderByDesc('active_events_count'),
                fn($query) => $query
            )
            ->orderBy('name');

        return $this->paginate($combinations);
    }

    /**
     * @param mixed $request
     * @return Builder[]|Collection
     */
    public function getExportList(mixed $request): Builder|Collection
    {
        return $this->getFilteredQuery($request)->get();
    }

    /**
     * @param string $ref
     * @return Model|Builder|\Illuminate\Database\Query\Builder
     */
    public function show(string $ref): \Illuminate\Database\Eloquent\Model|Builder|\Illuminate\Database\Query\Builder
    {
        $model = $this->getBuilderWithAnalytics()
            ->without('events')
            ->with([
                'eventCategory',
                'region',
                'city.region',
                'venue.city.region',
                'faqs',
                'meta',
                'image',
                'gallery'
            ])->where('ref', '=', $ref)
            ->withDrafted()
            ->firstOrFail();

        $model->append('draft_url');

        return $this->modelWithAppendedAnalyticsAttribute($model);
    }

    /**
     * @param string $slug
     * @return Model|Builder|\Illuminate\Database\Query\Builder
     */
    public function _show(string $slug): \Illuminate\Database\Eloquent\Model|Builder|\Illuminate\Database\Query\Builder
    {
//        try {
            return Combination::query()
                ->withOnly(['image', 'gallery', 'faqs:id,faqsable_id,faqsable_type,section,description', 'faqs.faqDetails:id,faq_id,question,answer,view_more_link', 'faqs.faqDetails.uploads'])
                ->withCount([
                    'events as active_events_count' => function ($query) {
                        $query->state(EventStateEnum::Live);
                    }
                ])
                ->where('slug', '=', $slug)
                ->when(request()->draft, fn($query) => $query->onlyDrafted())
                ->firstOrFail();
//        } catch (ModelNotFoundException $exception) {
////            dd('here');
//            throw new ModelNotFoundException("Combination with slug [$slug] not found.");
//        }
    }

    /**
     * @param string $path
     * @return Model|Builder|\Illuminate\Database\Query\Builder
     */
    public function _showByPath(string $path): \Illuminate\Database\Eloquent\Model|Builder|\Illuminate\Database\Query\Builder
    {
        return Combination::query()
            ->withOnly(['image', 'gallery', 'faqs', 'meta'])
            ->withCount([
                'events as active_events_count' => function ($query) {
                    $query->state(EventStateEnum::Live);
                }
            ])
            ->where('path', '=', $path)
            ->when(request()->draft, fn($query) => $query->onlyDrafted())
            ->firstOrFail();

//        return Combination::query()
//            ->withOnly(['image', 'gallery', 'faqs', 'events' => function ($query) {
//                $query
//                    ->state(EventStateEnum::Live)
//                    ->where(function ($q1) {
//                        $q1->orWhereHas('region', function ($q2) {
//                            $q2->where('id', 'combinations.region_id');
//                        })
//                        ->orWhereHas('city', function ($q2) {
//                            $q2->where('id', 'combinations.city_id');
//                        })
//                        ->orWhereHas('venue', function ($q2) {
//                            $q2->where('id', 'combinations.venue_id');
//                        })
//                        ->orWhereHas('eventCategories', function ($q2) {
//                            $q2->where('event_categories.id', 'combinations.event_category_id');
//                        });
//                    })
//                ;
//            }])
//            ->where('path', '=', $path)
//            ->firstOrFail();
    }

    /**
     * @param $year
     * @param $period
     * @param $type
     * @return array
     */
    public function generateStatsSummary($year, $period, $type): array
    {
        return CombinationStatistics::summary($year, $period, $type);
    }
}
