<?php

namespace App\Services\ClientOptions;

use App\Enums\AudiencesListOrderByFieldsEnum;
use App\Enums\AudienceSourceEnum;
use App\Enums\RoleNameEnum;
use App\Models\Audience;
use Illuminate\Support\Facades\Cache;

class AudienceOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        return Cache::remember('audiences-list-year-filter-options-'.clientSiteId(), now()->addHour(), function () {
            $years = Audience::selectRaw('DISTINCT YEAR(created_at) AS year')
                ->where('site_id', clientSiteId())
                ->whereNotNull('created_at')
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
    public static function getSourceOptions(): mixed
    {
        return Cache::remember('get-audiences-source-options', now()->addHour(), function () {
            return AudienceSourceEnum::_options()->map(function ($option, $key) {
                return [
                    'label' => ucfirst($option['label']),
                    'value' => $option['value']
                ];
            });
        });
    }

    /**
     * @return mixed
     */
    public static function getAuthorOptions(): mixed
    {
        return Cache::remember('get-audiences-author-options', now()->addHour(), function () {
            return RoleNameEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('get-audiences-order-by-options', now()->addHour(), function () {
            return AudiencesListOrderByFieldsEnum::_options();
        });
    }
}
