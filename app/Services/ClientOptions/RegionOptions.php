<?php

namespace App\Services\ClientOptions;

use App\Models\Region;
use Illuminate\Support\Facades\Cache;
use App\Enums\RegionsListOrderByFieldsEnum;

class RegionOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        return Cache::remember("regions-list-year-filter-options", now()->addHour(), function () {
            $years = Region::selectRaw('DISTINCT YEAR(created_at) AS year')
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
     * @return mixed
     */
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('regions-list-order-by-filter-options', now()->addHour(), function () {
            return RegionsListOrderByFieldsEnum::_options();
        });
    }
}
