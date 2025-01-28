<?php

namespace App\Services\DataServices\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DataServiceInterface
{
    /**
     * @param mixed $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder;

    /**
     * @param mixed $request
     * @return LengthAwarePaginator|array
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator|array;

    /**
     * @param mixed $request
     * @return Builder|Collection|\Illuminate\Support\Collection
     */
    public function getExportList(mixed $request): Builder|Collection|\Illuminate\Support\Collection;
}
