<?php

namespace App\Services\ClientOptions;

use Illuminate\Support\Facades\Cache;
use App\Enums\EventPropertyServicesListOrderByFieldsEnum;

class EventPropertyServiceOptions
{
    /**
     * @return mixed
     */
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('event-property-services-list-order-by-filter-options', now()->addHour(), function () {
            return EventPropertyServicesListOrderByFieldsEnum::_options();
        });
    }
}
