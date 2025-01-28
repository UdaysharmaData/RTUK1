<?php

namespace App\Services\ClientOptions;

use App\Models\Upload;
use Illuminate\Support\Facades\Cache;

class UploadOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        return Cache::remember("uploads-list-year-filter-options", now()->addHour(), function () {
            $years = Upload::selectRaw('DISTINCT YEAR(created_at) AS year')
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