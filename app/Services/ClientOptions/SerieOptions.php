<?php

namespace App\Services\ClientOptions;

use Illuminate\Support\Facades\Cache;

use App\Modules\Event\Models\Serie;
use App\Enums\DefaultListOrderByFieldsEnum;

class SerieOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        return Cache::remember("series-list-year-filter-options", now()->addHour(), function () {
            $years = Serie::selectRaw('DISTINCT YEAR(created_at) AS year')
                ->whereHas('site', function ($query) {
                    $query->makingRequest();
                })->whereNotNull('created_at')
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
