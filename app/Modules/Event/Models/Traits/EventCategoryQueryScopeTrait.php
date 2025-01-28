<?php

namespace App\Modules\Event\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Enums\EventCategoryVisibilityEnum;

trait EventCategoryQueryScopeTrait
{
    /**
     * Scope a query based on visibility.
     *
     * @param  Builder                       $query
     * @param  EventCategoryVisibilityEnum   $value
     * @return Builder
     */
    public function scopeVisibility(Builder $query, EventCategoryVisibilityEnum $value): Builder
    {
        return $query->where('visibility', $value);
    }

    /**
     * A query scope to filter event categories by site.
     *
     * @param Builder $query
     * @param bool $onlyAdministrator
     * @return Builder
     */
    public function scopeFilterBySite(Builder $query, bool $onlyAdministrator = true): Builder
    {
        return $query->whereHas('site', function ($q) use ($onlyAdministrator) {
            if ($onlyAdministrator) {
                $q->makingRequest()->hasAccess();
            } else {
                $q->makingRequest();
            }
        });
    }
}
