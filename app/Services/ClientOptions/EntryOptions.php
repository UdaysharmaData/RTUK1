<?php

namespace App\Services\ClientOptions;

use Auth;
use App\Http\Helpers\AccountType;
use Illuminate\Support\Facades\Cache;
use App\Enums\EntriesListOrderByFieldsEnum;
use App\Modules\Event\Models\EventEventCategory;

class EntryOptions
{
    /**
     * @return mixed
     */
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('entries-list-order-by-filter-options', now()->addHour(), function () {
            return EntriesListOrderByFieldsEnum::_options();
        });
    }
}
