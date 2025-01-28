<?php

namespace App\Services\ClientOptions;

use Illuminate\Support\Facades\Cache;
use App\Modules\Charity\Models\Charity;

class CharityOptions
{
    /**
     * @return mixed
      */
    public static function getYearOptions(): mixed
    {
        return Cache::remember("charities-list-year-filter-options", now()->addHour(), function () {
            $years = Charity::selectRaw('DISTINCT YEAR(created_at) AS year')
                ->whereNotNull('created_at')
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
