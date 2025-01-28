<?php

namespace App\Services\ClientOptions;

use App\Enums\BoolYesNoEnum;
use Illuminate\Support\Facades\Cache;
use App\Enums\ExternalEnquiryStatusEnum;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Enums\ExternalEnquiriesListOrderByFieldsEnum;

class ExternalEnquiryOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        return Cache::remember('external-enquiries-list-year-filter-options', now()->addMonth(), function () {
            $years = ExternalEnquiry::filterByAccess()
                ->where('site_id', clientSiteId())
                ->selectRaw('DISTINCT YEAR(created_at) AS year')
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

    /**
     * @return mixed
     */
    public static function getOrderByOptions(): mixed
    {
        return Cache::remember('external-enquiries-list-order-by-filter-options', now()->addHour(), function () {
            return ExternalEnquiriesListOrderByFieldsEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getStatusOptions(): mixed
    {
        return Cache::remember('external-enquiries-list-status-filter-options', now()->addHour(), function () {
            return ExternalEnquiryStatusEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getContactedOptions(): mixed
    {
        return Cache::remember('external-enquiries-list-contacted-filter-options', now()->addHour(), function () {
            return BoolYesNoEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getConvertedOptions(): mixed
    {
        return Cache::remember('external-enquiries-list-converted-filter-options', now()->addHour(), function () {
            return BoolYesNoEnum::_options();
        });
    }
}
