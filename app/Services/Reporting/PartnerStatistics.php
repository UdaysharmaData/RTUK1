<?php

namespace App\Services\Reporting;

use App\Modules\Setting\Models\Traits\SiteQueryScopeTrait;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Traits\OptionsTrait;
use App\Services\Reporting\Traits\PartnerStatsTrait;
use App\Services\Reporting\Traits\AnalyticsStatsTrait;

class PartnerStatistics
{
    use SiteQueryScopeTrait, OptionsTrait, AnalyticsStatsTrait, PartnerStatsTrait;

    const ENTITY = StatisticsEntityEnum::Partner;

    /**
     * @return array
     */
    public static function generateStatsSummary(): array
    {
        return [
            'stats' => [self::partnersStatsData()]
        ];
    }
}
