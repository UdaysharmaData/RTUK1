<?php

namespace App\Services\Reporting\Traits;

use App\Services\PercentageChange;
use App\Services\TimePeriodReferenceService;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HigherOrderWhenProxy;
use App\Http\Helpers\FormatNumber;
use App\Services\Reporting\Traits\PercentageChangeTrait;

use App\Modules\Event\Models\EventCategory;
use App\Enums\EventCategoryVisibilityEnum;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Enums\EventStatisticsTypeEnum;

trait EventCategoryStatsTrait
{
    use PercentageChangeTrait;

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param TimePeriodReferenceService|null $period
     * @param int|null $month
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function eventCategoriesSummaryQuery(?StatisticsEntityEnum $entity = null, ?int $year = null, ?string $status = null, ?TimePeriodReferenceService $period = null, ?int $month = null): Builder|HigherOrderWhenProxy|null
    {
        return EventCategory::query()
            ->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
            ->when($period, fn($query) => $query->where('created_at', '>=', $period->toCarbonInstance()))
            ->when($month, fn($query) => $query->whereMonth('created_at', '=', $month))
            ->when($status == EventCategoryVisibilityEnum::Public->value, fn($query) => $query->where('visibility', '=', EventCategoryVisibilityEnum::Public))
            ->when($status == EventCategoryVisibilityEnum::Private->value, fn($query) => $query->where('visibility', '=', EventCategoryVisibilityEnum::Private))
            ->when($entity == StatisticsEntityEnum::Participant, fn($query) => $query->has('participants'))
            ->when($entity == StatisticsEntityEnum::Event, fn($query) => $query->has('events'));
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param TimePeriodReferenceService|null $period
     * @return float
     */
    public static function eventCategoriesSummaryPercentChange(?StatisticsEntityEnum $entity = null, ?int $year = null, ?string $status = null, ?TimePeriodReferenceService $period = null): float
    {
        $query = self::eventCategoriesSummaryQuery($entity, null, $status);

        if ($year) {
            $previousYear = (string)($year - 1);

            $currentTotalCount = $query->clone()
                ->whereYear('event_categories.created_at', '=', $year)
                ->count();

            $previousTotalCount = $query->clone()
                ->whereYear('event_categories.created_at', '=', $previousYear)
                ->count();

            return (new PercentageChange)->calculate($currentTotalCount, $previousTotalCount);

        } elseif ($period) {
            $currentPeriod = $period->toCarbonInstance();
            $previousPeriod = $period->toCarbonInstance(true);
        } else {
            $currentPeriod = now()->subDays(self::getPercentChangeDays());
            $previousPeriod = $currentPeriod->copy()->subDays(self::getPercentChangeDays());
        }

        $currentTotalCount = $query->clone()
            ->where('event_categories.created_at', '>=', $currentPeriod)
            ->count();

        $previousTotalCount = $query->clone()
            ->where('event_categories.created_at', '>=', $previousPeriod)
            ->where('event_categories.created_at', '<', $currentPeriod)
            ->count();

        return (new PercentageChange)->calculate($currentTotalCount, $previousTotalCount);
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param TimePeriodReferenceService|null $period
     * @return \array[][]
     */
    protected static function eventCategoriesStatsData(?StatisticsEntityEnum $entity, ?int $year, ?string $status, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => 'Categories',
            'total' => FormatNumber::format(self::eventCategoriesSummaryQuery($entity, $year, $status, $period)->count()),
            'type_param_value' => 'event_categories'
        ];
    }
}
