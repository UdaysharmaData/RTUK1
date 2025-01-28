<?php

namespace App\Services\ClientOptions;

use Illuminate\Support\Facades\Cache;

use App\Models\Medal;
use App\Enums\MedalTypeEnum;
use App\Enums\MedalsListOrderByFieldsEnum;

class MedalOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        return Cache::remember("medals-list-year-filter-options", now()->addHour(), function () {
            $years = Medal::selectRaw('DISTINCT YEAR(created_at) AS year')
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
     *
     * @return mixed
     */
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('medals-list-order-by-filter-options', now()->addHour(), function () {
            return MedalsListOrderByFieldsEnum::_options();
        });
    }

    public static function getMedalTypeOptions(): mixed
    {
        return Cache::remember('medals-list-type-filter-options', now()->addHour(), function () {
            return MedalTypeEnum::_options();
        });
    }
}
