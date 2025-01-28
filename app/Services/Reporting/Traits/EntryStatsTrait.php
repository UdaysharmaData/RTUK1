<?php

namespace App\Services\Reporting\Traits;

use App\Http\Helpers\FormatNumber;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Traits\ParticipantStatsTrait;
use App\Services\Reporting\Enums\DashboardStatisticsTypeEnum;
use App\Services\TimePeriodReferenceService;
use Carbon\Carbon;

trait EntryStatsTrait
{
    use ParticipantStatsTrait;

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param int|null $month
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return \array[][]
     */
    protected static function entriesStatsData(?StatisticsEntityEnum $entity, ?int $year, ?string $status, ?string $category, ?int $month, ?int $userId = null, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => DashboardStatisticsTypeEnum::Entries->name,
            'total' => FormatNumber::format(self::participantsSummaryQuery($entity, $year, $status, $category, $month, $userId, $period)->count()),
            'percent_change' => self::participantsSummaryPercentChange($entity, $year, $status, $category, $month, $userId, $period),
            'type_param_value' => DashboardStatisticsTypeEnum::Entries->value
        ];
    }
}
