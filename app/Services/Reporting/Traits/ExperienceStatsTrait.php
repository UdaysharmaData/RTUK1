<?php

namespace App\Services\Reporting\Traits;

use Carbon\Carbon;
use App\Http\Helpers\FormatNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HigherOrderWhenProxy;
use App\Services\TimePeriodReferenceService;

use App\Models\Experience;
use App\Services\Reporting\Enums\StatisticsEntityEnum;

trait ExperienceStatsTrait
{
    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param TimePeriodReferenceService|null $period
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function experiencesSummaryQuery(?StatisticsEntityEnum $entity = null, ?int $year = null, ?TimePeriodReferenceService $period = null): Builder|HigherOrderWhenProxy|null
    {
        return Experience::query()
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
    protected static function experiencesStatsData(?StatisticsEntityEnum $entity, ?int $year, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => 'Experiences',
            'total' => FormatNumber::format(self::experiencesSummaryQuery($entity, $year, $period)->count()),
            'type_param_value' => 'experiences'
        ];
    }
}
