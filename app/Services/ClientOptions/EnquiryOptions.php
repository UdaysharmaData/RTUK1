<?php

namespace App\Services\ClientOptions;

use App\Enums\BoolYesNoEnum;
use Illuminate\Support\Facades\Cache;
use App\Modules\Enquiry\Models\Enquiry;

use App\Enums\EnquiryActionEnum;
use App\Enums\EnquiryStatusEnum;
use App\Enums\EnquiriesListOrderByFieldsEnum;

class EnquiryOptions
{
    /**
     * @return mixed
     */
    public static function getYearOptions(): mixed
    {
        return Cache::remember('enquiries_stats_year_filter_options', now()->addMonth(), function () {
            $years = Enquiry::filterByAccess()
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
        return Cache::remember('enquiries-list-order-by-filter-options', now()->addHour(), function () {
            return EnquiriesListOrderByFieldsEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getActionOptions(): mixed
    {
        return Cache::remember('enquiries-list-action-filter-options', now()->addHour(), function () {
            return EnquiryActionEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getStatusOptions(): mixed
    {
        return Cache::remember('enquiries-list-status-filter-options', now()->addHour(), function () {
            return EnquiryStatusEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getContactedOptions(): mixed
    {
        return Cache::remember('enquiries-list-contacted-filter-options', now()->addHour(), function () {
            return BoolYesNoEnum::_options();
        });
    }

    /**
     * @return mixed
     */
    public static function getConvertedOptions(): mixed
    {
        return Cache::remember('enquiries-list-converted-filter-options', now()->addHour(), function () {
            return BoolYesNoEnum::_options();
        });
    }
}
