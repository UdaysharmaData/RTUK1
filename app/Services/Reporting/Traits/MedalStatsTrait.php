<?php

namespace App\Services\Reporting\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HigherOrderWhenProxy;

use App\Models\Medal;
use App\Http\Helpers\FormatNumber;
use App\Services\TimePeriodReferenceService;
use App\Services\Reporting\Enums\StatisticsEntityEnum;

trait MedalStatsTrait
{
    /**
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function medalsSummaryQuery(?StatisticsEntityEnum $entity, ?int $year, ?TimePeriodReferenceService $period = null, ?string $_type): Builder|HigherOrderWhenProxy|null
    {
        return Medal::query()
            ->whereHas('site', function ($query) {
                $query->makingRequest();
            })
            ->when($year, fn ($query) => $query->whereYear('created_at', '=', $year))
            ->when($period, fn ($query) => $query->where('created_at', '>=', $period->toCarbonInstance()))
            ->when($_type, fn ($query) => $query->where('type', '=', $_type));
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param Carbon|null $period
     * @return \array[][]
     */
    protected static function medalsStatsData(?StatisticsEntityEnum $entity, ?int $year, ?TimePeriodReferenceService $period = null, ?string $_type): array
    {
        return [
            'name' => 'Medals',
            'total' => FormatNumber::format(self::medalsSummaryQuery($entity, $year, $period, $_type)->count()),
            'type_param_value' => 'medals'
        ];
    }
}
