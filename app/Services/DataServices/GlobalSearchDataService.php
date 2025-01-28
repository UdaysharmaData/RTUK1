<?php

namespace App\Services\DataServices;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

use App\Services\DataServices\Contracts\DataServiceInterface;
use App\Services\GlobalSearchService\GlobalSearch;

class GlobalSearchDataService extends GlobalSearch implements DataServiceInterface {
 /**
     * @param mixed $request
     * @return Builder
     */
    public function getFilteredQuery(mixed $request): Builder
    {
        $model = self::SEARCHABLE_MODELS[0];
        $query = $model::query();

        if ($request->has('term')) {
            $query->where('name', 'LIKE', "%{$request->term}%");
        }

        return $query;
    }

    /**
     * @param mixed $request
     * @return LengthAwarePaginator|array
     */
    public function getPaginatedList(mixed $request): LengthAwarePaginator|array
    {
        $query = $this->getFilteredQuery($request);

        return $query->paginate($request->per_page ?? 10);
    }

    /**
     * @param mixed $request
     * @return Builder|Collection
     */
    public function getExportList(mixed $request): Builder|Collection
    {
        $query = $this->getFilteredQuery($request);

        return $query->get();
    }
}