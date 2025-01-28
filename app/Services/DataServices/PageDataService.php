<?php

namespace App\Services\DataServices;

use App\Enums\PageStatus;
use App\Filters\DeletedFilter;
use App\Filters\DraftedFilter;
use App\Filters\FaqsFilter;
use App\Filters\MetaKeywordsFilter;
use App\Filters\PageOrderByFilter;
use App\Filters\PeriodFilter;
use App\Filters\YearFilter;
use App\Models\Page;
use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\Reporting\PageStatistics;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PageDataService extends DataService implements DataServiceInterface
{
    /**
     * @var bool
     */
    private bool $loadRedirect = false;

    public function __construct()
    {
        $this->builder = Page::query();
        $this->appendAnalyticsData = false;
    }

    /**
     * @param bool $value
     * @return PageDataService
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
        $status = request('status');
        $parameters = array_filter(request()->query());

        $query = $this->builder
            ->when($this->loadRedirect, fn($query) => $query->with('redirect'))
            ->filterListBy(new DraftedFilter)
            ->filterListBy(new DeletedFilter)
            ->filterListBy(new PageOrderByFilter)
            ->filterListBy(new PeriodFilter)
            ->filterListBy(new YearFilter)
            ->filterListBy(new FaqsFilter)
            ->filterListBy(new MetaKeywordsFilter);

        if (count($parameters) === 0) {
            $query = $query->latest();
        }

        return $query
            ->when(! is_null($status), function (Builder $query) use ($status) {
                if ((int)$status === PageStatus::Online->value) {
                    $query->online();
                } elseif ((int)$status === PageStatus::Offline->value) {
                    $query->offline();
                }
            })
            ->when($term, $this->applySearchTermFilter($term));
    }

    /**
     * @param string|null $term
     * @return \Closure
     */
    private function applySearchTermFilter(string|null $term): \Closure
    {
        return function (Builder $query) use ($term) {
            if (isset($term)) {
                $query->where('url', 'LIKE', "%$term%")
                    ->orWhere('name', 'LIKE', "%$term%")
                    ->orWhereHas('meta', function (Builder $query) use($term) {
                        $query->where('title', 'LIKE', "%$term%")
                            ->orWhere('description', 'LIKE', "%$term%")
                            ->orWhereJsonContains('keywords', $term);
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
        $data = $this->paginate($this->getBuilderWithAnalytics($this->getFilteredQuery($request)))->through(function ($page) {
            $page = $page->append('draft_url');

            return $page;
        });
        
        return $data;
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
     * Portal Show
     * @param string $ref
     * @return Model|Builder|\Illuminate\Database\Query\Builder
     */
    public function edit(string $ref): \Illuminate\Database\Eloquent\Model|Builder|\Illuminate\Database\Query\Builder
    {
        $model = $this->getBuilderWithAnalytics()
            ->when($this->loadRedirect, fn($query) => $query->with('redirect'))
            ->withDrafted()
            ->withTrashed()
            ->where('pages.ref', '=', $ref)
            ->firstOrFail();

        return $this->modelWithAppendedAnalyticsAttribute($model);
    }

    /**
     * Client Show
     * @param string $ref
     * @return Model|Builder|\Illuminate\Database\Query\Builder
     */
    public function show(string $ref): \Illuminate\Database\Eloquent\Model|Builder|\Illuminate\Database\Query\Builder
    {
        return $this->builder
            ->withoutEagerLoad(['faqs'])
//            ->withTrashed()
            ->where('ref', '=', $ref)
            ->firstOrFail();
    }

    /**
     * @param $year
     * @param $period
     * @return array
     */
    public function generateStatsSummary($year, $period): array
    {
        return PageStatistics::summary($year, $period);
    }
}
