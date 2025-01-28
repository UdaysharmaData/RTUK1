<?php

namespace App\Services\ClientOptions;

use Illuminate\Support\Facades\Cache;

use App\Modules\Event\Models\Sponsor;
use App\Enums\DefaultListOrderByFieldsEnum;

class SponsorOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        return Cache::remember("sponsors-list-year-filter-options", now()->addHour(), function () {
            $years = Sponsor::selectRaw('DISTINCT YEAR(created_at) AS year')
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
