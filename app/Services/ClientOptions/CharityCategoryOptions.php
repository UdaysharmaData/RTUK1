<?php

namespace App\Services\ClientOptions;

use Illuminate\Support\Facades\Cache;
use App\Modules\Charity\Models\CharityCategory;

class CharityCategoryOptions
{
    /**
     * @return mixed
     */
    public static function getRefOptions(): mixed
    {
        return Cache::remember('charity_categories_stats_ref_filter_options', now()->addHour(), function () {
            $categories = CharityCategory::orderBy('name')->pluck('name', 'ref');

            return $categories->map(function ($option, $key) {
                return [
                    'label' => $option,
                    'value' => $key
                ];
            })->values();
        });
    }
}
