<?php

namespace App\Services\ClientOptions;

use Carbon\Carbon;
use App\Http\Helpers\AccountType;
use App\Modules\Event\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Enums\EventCategoryVisibilityEnum;
use App\Modules\Event\Models\EventCategory;
use App\Enums\EventCategoriesListOrderByFieldsEnum;

class EventCategoryOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        return Cache::remember('event-categories-list-year-filter-options', now()->addMonth(), function () {
            $years = EventCategory::where('site_id', clientSiteId())
                ->selectRaw('DISTINCT YEAR(created_at) AS year')
                ->whereHas('site', function ($query) {
                    $query->makingRequest();
                })->whereNotNull('created_at')
                // ->orderByDesc('created_at')
                ->pluck('year')
                ->sortDesc();

            return $years->map(function ($option, $key) {
                return [
                    'label' => (string) $option,
                    'value' => $option
                ];
            })->values();
        });
    }

    /**
     * @param  string  $for
     * @return mixed
     */
    public static function getRefOptions(?string $for = null): mixed
    {
        $userId = AccountType::isParticipant() ? Auth::user()->id : null;

        return Cache::remember("event-categories-ref-filter-options-{$for}-{$userId}", now()->addHour(), function () use ($for) {
            $categories = EventCategory::orderBy('name')
                ->whereHas('site', function ($query) {
                    $query->makingRequest();
                })->when($for && $for == 'entries' && AccountType::isParticipant(),
                    fn ($query) => $query->whereHas('participants', function ($query) {
                        $query->where('user_id', Auth::user()->id);
                    })->whereHas('events', function ($query) {
                        // $query->state(EventStateEnum::Live);
                        $query->estimated(Event::INACTIVE);
                        $query->archived(Event::INACTIVE);
                        $query->partnerEvent(Event::ACTIVE);
                        $query->where('status', Event::ACTIVE);
                        $query->where('end_date', '>', Carbon::now());
                    })
                )->pluck('name', 'ref');

            return $categories->map(function ($option, $key) {
                return [
                    'label' => $option,
                    'value' => $key
                ];
            })->values();
        });
    }

    /**
     * @return mixed
     */
    public static function getVisibilityOptions(): mixed
    {
        return Cache::remember('event-categories-list-visibility-filter-options', now()->addHour(), function () {
            return EventCategoryVisibilityEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getStatusOptions(): mixed
    {
        return Cache::remember('event-categories-list-status-filter-options', now()->addHour(), function () {
            return EventCategoryVisibilityEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('event-categories-list-order-by-filter-options', now()->addHour(), function () {
            return EventCategoriesListOrderByFieldsEnum::_options();
        });
    }
}
