<?php

namespace App\Traits;

use App\Services\Analytics\Contracts\AnalyzableInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait PaginationTrait
{
    /**
     * Paginate the data
     *
     * @param \Illuminate\Database\Query\Builder|Builder|BelongsToMany|HasMany  $query
     * @return LengthAwarePaginator
     */
    protected function paginate(\Illuminate\Database\Query\Builder|Builder|BelongsToMany|HasMany $query): LengthAwarePaginator
    {
        $this->paginatedList = $query->when((int)$perPage = request('per_page'),
            fn($query) => $query->paginate($perPage),
            fn($query) => $query->paginate(10)
        )->withQueryString();

        if ($this->appendAnalyticsData) {
            $this->appendAnalyticsAttributeToPaginatedList();
        }

        return $this->paginatedList;
    }
}
