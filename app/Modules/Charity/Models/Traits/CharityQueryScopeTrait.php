<?php

namespace App\Modules\Charity\Models\Traits;

use App\Enums\CharityMembershipTypeEnum;
use Auth;
use Carbon\Carbon;
use App\Enums\EventTypeEnum;
use App\Enums\EventStateEnum;
use App\Http\Helpers\AccountType;
use App\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Builder;

trait CharityQueryScopeTrait
{
    /**
     * Scope a query to filter by status
     *
     * @param  Builder  $query
     * @param  int      $value
     * @return Builder
     */
    public function scopeStatus(Builder $query, int $value): Builder
    {
        return $query->where('status', $value);
    }

    /**
     * Scope a query to filter by membership type
     *
     * @param  Builder                        $query
     * @param  CharityMembershipTypeEnum      $value
     * @return Builder
     */
    public function scopeMembershipType(Builder $query, CharityMembershipTypeEnum $value): Builder
    {
        return $query->whereHas('charityMemberships', function ($query) use ($value) {
            $query->where('type', $value);
        });
    }

    /**
     * Scope a query to filter by latest membership type
     *
     * @param  Builder                        $query
     * @param  CharityMembershipTypeEnum      $value
     * @return Builder
     */
    public function scopeLatestMembershipType(Builder $query, CharityMembershipTypeEnum $value): Builder
    {
        return $query->whereHas('latestCharityMembership', function ($query) use ($value) {
            $query->where('type', $value);
        });
    }

    /**
     * A query scope to filter by user access.
     * 
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeFilterByAccess(Builder $query): Builder
    {
        // if (AccountType::isCharityOwnerOrCharityUser()) {
        //     $query = $charity->whereHas('users', function($query) {
        //         $query->where('users.id', Auth::user()->id);
        //     });
        // }

        if (AccountType::isCharityOwner()) {
            $query = $query->whereHas('charityOwner', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        if (AccountType::isCharityUser()) {
            $query = $query->whereHas('charityUsers', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        if (AccountType::isAccountManager()) {
            $query = $query->whereHas('charityManager', function($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        return $query;
    }
}