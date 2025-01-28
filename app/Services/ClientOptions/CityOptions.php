<?php

namespace App\Services\ClientOptions;

use App\Models\City;
use Illuminate\Support\Facades\Cache;

class CityOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        return Cache::remember("cities-list-year-filter-options", now()->addHour(), function () {
            $years = City::selectRaw('DISTINCT YEAR(created_at) AS year')
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
}
