<?php

namespace App\Services\ClientOptions;

use App\Models\Experience;
use Illuminate\Support\Facades\Cache;
use App\Enums\ExperiencesListOrderByFieldsEnum;

class ExperienceOptions
{
    /**
     * @return mixed
    */
    public static function getYearOptions(): mixed
    {
        return Cache::remember("experiences-list-year-filter-options", now()->addHour(), function () {
            $years = Experience::selectRaw('DISTINCT YEAR(created_at) AS year')
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
        return Cache::remember('experiences-list-order-by-filter-options', now()->addHour(), function () {
            return ExperiencesListOrderByFieldsEnum::_options();
        });
    }
}
