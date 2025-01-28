<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Filterable
{
    /**
     * @param Builder $builder
     * @return Builder
     */
    public function apply(Builder $builder): Builder;
}
