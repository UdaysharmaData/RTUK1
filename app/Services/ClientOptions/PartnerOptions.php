<?php

namespace App\Services\ClientOptions;

use Illuminate\Support\Facades\Cache;
use App\Enums\PartnersListOrderByFieldsEnum;

class PartnerOptions
{
    /**
     * @return mixed
     */
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('partners-list-order-by-filter-options', now()->addHour(), function () {
            return PartnersListOrderByFieldsEnum::_options();
        });
    }
}
