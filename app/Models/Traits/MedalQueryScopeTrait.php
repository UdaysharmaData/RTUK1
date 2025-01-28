<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait MedalQueryScopeTrait
{

    /**
     * A query scope to filter medals by site.
     *
     * @param Builder $builder
     * @param bool $onlyAdministrator
     * @return Builder
     */
    public function scopeFilterBySite(Builder $builder, $onlyAdministrator = true): Builder
    {
        return $builder->whereHas('site', function ($q) use ($onlyAdministrator) {
            if ($onlyAdministrator) {
                $q->makingRequest()->hasAccess();
            } else {
                $q->makingRequest();
            }
        });
    }
}
