<?php

namespace App\Modules\Event\Models\Traits;

use Auth;
use Carbon\Carbon;
use App\Enums\EventStateEnum;
use App\Enums\EventCharitiesEnum;
use App\Http\Helpers\AccountType;
use App\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Builder;

trait EventQueryScopeTrait
{
    /**
     * Scope a query to only include active/non-active events.
     *
     * @param Builder $query
     * @param bool $value
     * @return Builder
     */
    public function scopeActive(Builder $query, bool $value): Builder
    {
        return $value
            ? $query->estimated(Event::INACTIVE)
            ->archived(Event::INACTIVE)
            ->where('status', Event::ACTIVE)
            : $query->where(function ($query) {
                $query->where('estimated', Event::ACTIVE)
                    ->orWhere('archived', Event::ACTIVE)
                    ->orWhere('status', Event::INACTIVE);
            });
    }

    /**
     * Scope a query to only include live, expired, or archived events.
     *
     * @param Builder $query
     * @param EventStateEnum $value
     * @return Builder
     */
    public function scopeState(Builder $query, EventStateEnum $value): Builder
    {
        switch ($value) {
            case EventStateEnum::Live:
                $query = $query->active(true)
                    ->whereHas('eventCategories', function ($q) {
                        $q->where('end_date', '>', Carbon::now());
                    });

                break;

            case EventStateEnum::Expired:
                $query = $query/*->where('status', Event::ACTIVE)*/
                    ->whereHas('eventCategories', function ($q1) {
                        $q1->where('end_date', '<', Carbon::now());
                    })
                    ->archived(Event::INACTIVE);

                break;

            case EventStateEnum::Archived:
                $query = $query->archived(Event::ACTIVE);

                break;
        }

        return $query;
    }

    /**
     * Scope a query to only include archived events.
     *
     * @param Builder $query
     * @param bool $value
     * @return Builder
     */
    public function scopeArchived(Builder $query, bool $value): Builder
    {
        return $query->where('archived', $value);
    }

    /**
     * Scope a query to only include estimated events.
     *
     * @param Builder $query
     * @param bool $value
     * @return Builder
     */
    public function scopeEstimated(Builder $query, bool $value): Builder
    {
        return $query->where('estimated', $value);
    }

    /**
     * Scope a query to only include partner events.
     *
     * @param Builder $query
     * @param bool $value
     * @return Builder
     */
    public function scopePartnerEvent(Builder $query, bool $value): Builder
    {
        return $query->where('partner_event', $value);
    }

    /**
     * A query scope to filter events by user access.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeFilterByAccess(Builder $query): Builder
    {
        if (AccountType::isCharityOwnerOrCharityUser()) {
            $query = $query->where(function ($query) {
                $query->where('charities', EventCharitiesEnum::All)
                    ->orWhere(function ($query) {
                        $query->where('charities', EventCharitiesEnum::Included)
                            ->whereHas('includedCharities.users', function ($query) {
                                $query->where('users.id', \Auth::user()->id);
                            });
                    })
                    ->orWhere(function ($query) {
                        $query->where('charities', EventCharitiesEnum::Excluded)
                            ->whereDoesntHave('excludedCharities.users', function ($query) {
                                $query->where('users.id', \Auth::user()->id);
                            });
                    });
            });
        }

        if (AccountType::isEventManager()) {
            $query = $query->whereHas('eventManagers', function ($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        if (AccountType::isParticipant()) { // Always return live events for this user
            $query = $query->state(EventStateEnum::Live);
        }

        return $query;
    }

    /**
     * A query scope to filter events by site.
     * @param Builder $query
     * @param bool $onlyAdministrator
     * @return Builder
     */
    public function scopeFilterBySite(Builder $query, bool $onlyAdministrator = true): Builder
    {
        return $query->whereHas('eventCategories', function ($query) use ($onlyAdministrator) {
            $query->whereHas('site', function ($q) use ($onlyAdministrator) {
                if ($onlyAdministrator) {
                    $q->makingRequest()->hasAccess();
                } else {
                    $q->makingRequest();
                }
            });
        });
    }

    public function scopeWithoutRelations(Builder $query): Builder
    {
        return $query->withOnly([]);
    }
}
