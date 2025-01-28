<?php

namespace App\Services\ClientOptions;

use Illuminate\Support\Facades\Cache;
use App\Enums\PartnerChannelsListOrderByFieldsEnum;

class PartnerChannelOptions
{
    /**
     * @return mixed
     */
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('partner-channels-list-order-by-filter-options', now()->addHour(), function () {
            return PartnerChannelsListOrderByFieldsEnum::_options();
        });
    }
}
