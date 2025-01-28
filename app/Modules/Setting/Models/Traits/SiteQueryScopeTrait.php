<?php

namespace App\Modules\Setting\Models\Traits;

use Auth;
use App\Traits\SiteTrait;
use App\Enums\SiteUserStatus;
use App\Http\Helpers\AccountType;
 
use Illuminate\Database\Eloquent\Builder;

trait SiteQueryScopeTrait
{
    use SiteTrait;

    /**
     * Scope a query to only get the resources based on the site the user has access to
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeHasAccess(Builder $query): Builder
    {
        return $query->whereHas('users', function ($query) {
            $query->where('user_id', Auth::user()->id)
                ->where('status', SiteUserStatus::Active->value);
        });
    }

    /**
     * Scope a query to only get the resources based on the site making the request
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeMakingRequest(Builder $query): Builder
    {
        if (request()->filled('site')) {
            $query->where('sites.ref', request()->site);
        }

        // Checking if the user is not a general administrator 
        if (! AccountType::isGeneralAdmin()) {
            $query = $query->where('sites.id', static::getSite()?->id);
        }

        return $query;
    }
}
