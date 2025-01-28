<?php

namespace App\Services\Reporting\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HigherOrderWhenProxy;

use App\Http\Helpers\FormatNumber;
use App\Modules\Event\Models\Sponsor;
use App\Services\TimePeriodReferenceService;
use App\Services\Reporting\Enums\StatisticsEntityEnum;

trait SponsorStatsTrait
{
    /**
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function sponsorsSummaryQuery(?StatisticsEntityEnum $entity, ?int $year, ?TimePeriodReferenceService $period = null): Builder|HigherOrderWhenProxy|null
    {
        return Sponsor::query()
            ->whereHas('site', function ($query) {
                $query->makingRequest();
            })
            ->when($year, fn ($query) => $query->whereYear('created_at', '=', $year))
            ->when($period, fn ($query) => $query->where('created_at', '>=', $period->toCarbonInstance()));
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param Carbon|null $period
     * @return \array[][]
     */
    protected static function sponsorsStatsData(?StatisticsEntityEnum $entity, ?int $year, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => 'Sponsors',
            'total' => FormatNumber::format(self::sponsorsSummaryQuery($entity, $year, $period)->count()),
            'type_param_value' => 'sponsors'
        ];
    }
}
