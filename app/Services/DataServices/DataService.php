<?php

namespace App\Services\DataServices;

use App\Services\Analytics\Contracts\AnalyzableInterface;
use App\Services\Analytics\Pipes\AddInteractionCountStatPipe;
use App\Services\Analytics\Pipes\AddViewCountStatPipe;
use App\Traits\PaginationTrait;
use App\Services\DataServices\Contracts\DataServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pipeline\Pipeline;

abstract class DataService implements DataServiceInterface
{
    use PaginationTrait;

    /**
     * @var Builder|null
     */
    protected ?Builder $builder = null;

    /**
     * @var LengthAwarePaginator|null
     */
    protected ?LengthAwarePaginator $paginatedList = null;

    /**
     * @var bool
     */
    protected bool $appendAnalyticsData = false;

    /**
     * @param mixed $request
     * @return Builder
     */
    public abstract function getFilteredQuery(mixed $request): Builder;

    /**
     * @param mixed $request
     * @return LengthAwarePaginator|array
     */
    public abstract function getPaginatedList(mixed $request): LengthAwarePaginator|array;

    /**
     * @param mixed $request
     * @return Builder|Collection|\Illuminate\Support\Collection
     */
    public abstract function getExportList(mixed $request): Builder|Collection|\Illuminate\Support\Collection;

    /**
     * @return $this
     */
    protected function appendAnalyticsAttributeToPaginatedList(): DataService
    {
        if (! is_null($this->paginatedList)) {
            $this->paginatedList = $this->paginatedList->through(function (mixed $model) {
                return $this->appendAnalytics($model);
            });
        }

        return $this;
    }

    /**
     * @param Model|AnalyzableInterface $model
     * @return Model
     */
    public function modelWithAppendedAnalyticsAttribute(Model|AnalyzableInterface $model): Model
    {
        $model->withExtras = true;

        return $this->appendAnalytics($model);
    }

    /**
     * @param Builder|null $builder
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    protected function getBuilderWithAnalytics(Builder $builder = null): Builder|\Illuminate\Database\Query\Builder
    {
        $this->appendAnalyticsData = true;

        if (! is_null($builder)) {
            $this->builder = $builder;
        }

        if (
            (! is_null($this->builder))
            && ($this->builder->getModel() instanceOf AnalyzableInterface)
        ) {
            return $this->builder = app(Pipeline::class)
                ->send($this->builder)
                ->through([
                    AddViewCountStatPipe::class,
                    AddInteractionCountStatPipe::class
                ])->thenReturn();
        }

        return $this->builder;
    }

    /**
     * @param Model|AnalyzableInterface $model
     * @return Model
     */
    private function appendAnalytics(Model|AnalyzableInterface $model): Model
    {
        if ($model instanceof AnalyzableInterface) {
            return $model->append([
                'view_stats',
                'interaction_stats'
            ]);
        } else return $model;
    }
}
