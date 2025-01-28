<?php

namespace App\Services\Reporting\Traits;

use App\Services\TimePeriodReferenceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HigherOrderWhenProxy;
use App\Http\Helpers\FormatNumber;

use App\Models\Region;
use App\Services\Reporting\Enums\StatisticsEntityEnum;

trait RegionStatsTrait
{
    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param TimePeriodReferenceService|null $period
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function regionsSummaryQuery(?StatisticsEntityEnum $entity = null, ?int $year = null, ?TimePeriodReferenceService $period = null): Builder|HigherOrderWhenProxy|null
    {
        return Region::query()
            ->whereHas('site', function ($query) {
                $query->makingRequest();
            })
            ->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
            ->when($period, fn($query) => $query->where('created_at', '>=', $period->toCarbonInstance()));
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param TimePeriodReferenceService|null $period
     * @return \array[][]
     */
    protected static function regionsStatsData(?StatisticsEntityEnum $entity, ?int $year, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => 'Regions',
            'total' => FormatNumber::format(self::regionsSummaryQuery($entity, $year, $period)->count()),
            'type_param_value' => 'regions'
        ];
    }
}
