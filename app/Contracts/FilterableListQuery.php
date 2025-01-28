<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface FilterableListQuery
{
    /**
     * @param Builder $builder
     * @param Filterable $filter
     * @return Builder
     */
    public function scopeFilterListBy(Builder $builder, Filterable $filter): Builder;
}
