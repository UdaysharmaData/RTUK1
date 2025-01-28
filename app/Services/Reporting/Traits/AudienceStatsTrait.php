<?php

namespace App\Services\Reporting\Traits;

use App\Http\Helpers\FormatNumber;
use App\Models\Audience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HigherOrderWhenProxy;
use App\Services\TimePeriodReferenceService;

use App\Services\Reporting\Enums\StatisticsEntityEnum;

trait AudienceStatsTrait
{
    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param TimePeriodReferenceService|null $period
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function audiencesSummaryQuery(?StatisticsEntityEnum $entity = null, ?int $year = null, ?TimePeriodReferenceService $period = null): Builder|HigherOrderWhenProxy|null
    {
        return Audience::query()
            ->where('site_id', clientSiteId())
            ->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
            ->when($period, fn($query) => $query->where('created_at', '>=', $period->toCarbonInstance()));
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param TimePeriodReferenceService|null $period
     * @return \array[][]
     */
    protected static function audiencesStatsData(?StatisticsEntityEnum $entity, ?int $year, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => 'Audiences',
            'total' => FormatNumber::format(self::audiencesSummaryQuery($entity, $year, $period)->count()),
        ];
    }
}
