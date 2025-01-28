<?php

namespace App\Traits;

use App\Contracts\Filterable;
use Illuminate\Database\Eloquent\Builder;

trait FilterableListQueryScope
{
    /**
     * @param Builder $builder
     * @param Filterable $filter
     * @return Builder
     */
    public function scopeFilterListBy(Builder $builder, Filterable $filter): Builder
    {
        return $filter->apply($builder);
    }
}
