<?php

namespace App\Services\ClientOptions;

use App\Enums\EventTypeEnum;
use App\Enums\EventStateEnum;
use App\Modules\Event\Models\Event;
use Illuminate\Support\Facades\Cache;
use App\Enums\EventsListOrderByFieldsEnum;
use App\Modules\Event\Models\EventEventCategory;

class EventOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        return Cache::remember('events-list-year-filter-options', now()->addHour(), function () {
            $years = EventEventCategory::whereHas('eventCategory', function ($query) {
                    $query->where('site_id', clientSiteId());
                })->selectRaw('DISTINCT YEAR(start_date) AS year')
                ->whereNotNull('start_date')
                // ->orderByDesc('start_date')
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
     * @return mixed
     */
    public static function getRefOptions(): mixed
    {
        return Cache::remember('events-ref-filter-options', now()->addHour(), function () {
            $categories = Event::orderBy('name')->pluck('name', 'ref');

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
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('events-list-order-by-filter-options', now()->addHour(), function () {
            return EventsListOrderByFieldsEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getStateOptions(): mixed
    {
        return Cache::remember('events-list-state-filter-options', now()->addHour(), function () {
            return EventStateEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getTypeOptions(): mixed
    {
        return Cache::remember('events-list-type-filter-options', now()->addHour(), function () {
            return EventTypeEnum::_options();
        });
    }
}
