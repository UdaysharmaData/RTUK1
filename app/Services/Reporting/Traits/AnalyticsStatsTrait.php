<?php

namespace App\Services\Reporting\Traits;

use App\Enums\TimeReferenceEnum;
use App\Http\Helpers\FormatNumber;
use App\Services\Analytics\AnalyticsViewsStats;
use App\Services\Analytics\AnalyticsInteractionsStats;

trait AnalyticsStatsTrait
{
    /**
     * @param string $class
     * @param string|null $period
     * @param string|null $year
     * @return \array[][]
     */
    public static function getAnalytics(string $class, ?string $period = null, ?string $year = null): array
    {
        $period = $period ?: TimeReferenceEnum::All->value;

        return [
            [
                'name' => 'Views',
                'total' => FormatNumber::format(AnalyticsViewsStats::combinedViewsCount($class, $period, $year)),
                'percent_change' => AnalyticsViewsStats::combinedViewsCountPercentChange($class, $period, $year),
                'type_param_value' => 'views'
            ],
            [
                'name' => 'Interactions',
                'total' => FormatNumber::format(AnalyticsInteractionsStats::combinedInteractionsCount($class, $period, $year)),
                'percent_change' => AnalyticsInteractionsStats::combinedInteractionsCountPercentChange($class, $period, $year),
                'type_param_value' => 'interactions'
            ]
        ];
    }
}
