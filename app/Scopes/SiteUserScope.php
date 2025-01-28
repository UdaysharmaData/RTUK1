<?php

namespace App\Scopes;

use App\Traits\SiteTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SiteUserScope implements Scope
{
    use SiteTrait;

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas(
            'sites',
            fn(Builder $query) => $query->where('id', '=', static::getSite()?->id)
        );
    }
}
