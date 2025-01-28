<?php

namespace App\Traits;

use App\Modules\Setting\Enums\OrganisationEnum;
use App\Modules\Setting\Enums\SiteEnum;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait CustomPaginationTrait
{
    use SiteTrait, PaginationTrait;

    /**
     * Get the pagination for the given query.
     *
     * @param  \Illuminate\Database\Query\Builder|Builder|BelongsToMany|HasMany  $query
     * @return array
     */
    protected static function customPaginate(\Illuminate\Database\Query\Builder|Builder|BelongsToMany|HasMany $query, Request $request): array
    {
        $query = $query;
        $clone = $query->clone();

        $skip = (int) $request->skip ?? null;
        $take = (int) $request->take ?? null;

        $events = $query->when($skip || $take,
            function ($query) use ($skip, $take) {
                if ($skip) {
                    $query->skip($skip);
                }

                if ($take) {
                    $query->take($take);
                }

                return $query->get();
            },
            fn($query) => $query->take(10)->get()
        );

        return [
            'query_params' => count($params = array_filter(request()->query(),
                function ($var) {
                    return isset($var);
                })
            ) > 0
                ? $params
                : null,
            'total' => $clone->toBase()->getCountForPagination(),
            'data' => $events,
        ];
    }

    /**
     * Get site based pagination for the given query.
     *
     * @param Builder|BelongsToMany|HasMany $query
     * @param Request $request
     * @return array|LengthAwarePaginator
     */
    protected function siteBasedPagination(Builder|BelongsToMany|HasMany $query, Request $request): array|LengthAwarePaginator
    {
        if (SiteEnum::belongsToOrganisation(OrganisationEnum::GWActive)) { // RunThrough Pagination is different from other sites pagination since they display items with ads
            return self::customPaginate($query, $request);
        } else {
            // if (app()->environment('production')) { // TODO: Remove / Undo this during Runthrough_Deployment
            //     return $this->paginate($query);
            // } else {
               // return $this->paginate($query);
                 return self::customPaginate($query, $request);
            // }
        }
    }
}
