<?php

namespace App\Services\Reporting\Traits;

use App\Enums\TimeReferenceEnum;
use App\Traits\SiteTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

use App\Enums\MonthEnum;
use App\Enums\InvoiceStatusEnum;
use App\Services\Reporting\Enums\EventStatisticsTypeEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\EventCategoryVisibilityEnum;

trait OptionsTrait
{
    use SiteTrait;

    /**
     * @return int
     */
    protected static function getPercentChangeDays(): int
    {
        $siteCode = clientSiteCode();
        return config("apiclient.$siteCode.percent_change_days", 7);
    }

    /**
     * @return array
     */
    protected static function months(): array
    {
        return MonthEnum::options();
    }

    /**
     * @param Builder $builder
     * @param string|int $month
     * @return int
     */
    protected static function getChartTotalMonthCount(Builder $builder, string|int $month): int
    {
        return $builder
            ->whereMonth('created_at', '=', $month)
            ->count();
    }

    /**
     * @return mixed
     */
    public static function getPeriodOptions(): mixed
    {
        return Cache::remember('dashboard_stats_period_filter_options', now()->addMonth(), function () {
            $days = collect(TimeReferenceEnum::values());

            return $days->map(function ($option, $key) {
                return [
                    'label' => $option,
                    'value' => $option
                ];
            });
        });
    }
}
