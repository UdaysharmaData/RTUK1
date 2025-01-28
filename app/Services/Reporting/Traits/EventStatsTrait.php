<?php

namespace App\Services\Reporting\Traits;

use App\Services\PercentageChange;
use App\Services\TimePeriodReferenceService;
use Carbon\Carbon;
use App\Http\Helpers\AccountType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HigherOrderWhenProxy;
use App\Http\Helpers\FormatNumber;

use App\Enums\EventStateEnum;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Traits\PercentageChangeTrait;
use App\Services\Reporting\Enums\EventStatisticsTypeEnum;

use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventEventCategory;

trait EventStatsTrait
{
    use PercentageChangeTrait, OptionsTrait;

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param string|null $status
     * @param string|null $category
     * @param int|null $year
     * @param int|null $month
     * @param TimePeriodReferenceService|null $period
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function eventsSummaryQuery(?StatisticsEntityEnum $entity = null, ?string $status = null, ?string $category = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): Builder|HigherOrderWhenProxy|null
    {
        return Event::query()
            ->when($entity == StatisticsEntityEnum::Enquiry, fn($query) => $query->whereHas('enquiries', function ($query) {
                $query->where('site_id', clientSiteId());
            }))
            ->when($entity == StatisticsEntityEnum::ExternalEnquiry, fn($query) => $query->whereHas('externalEnquiries', function ($query) {
                $query->where('site_id', clientSiteId());
            }))
            ->whereHas('eventCategories', function ($query) use ($period, $category, $year, $month) {
                $query->where('site_id', clientSiteId())
                    ->when($category, fn($query) => $query->where('event_categories.ref', $category))
                    ->when($year, fn($query) => $query->whereYear('start_date', '=', $year))
                    ->when($month, fn($query) => $query->whereMonth('start_date', '=', $month))
                    ->when($period, fn($query) => $query->where('start_date', '>=', $period->toCarbonInstance()));
            })->when($status, fn($query) => $query->state(EventStateEnum::from($status)));
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param string|null $status
     * @param string|null $category
     * @param int|null $year
     * @param int|null $month
     * @param TimePeriodReferenceService|null $period
     * @return float
     */
    public static function eventsSummaryPercentChange(?StatisticsEntityEnum $entity = null, ?string $status = null, ?string $category = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): float
    {
        $query = self::eventsSummaryQuery($entity, $status, $category, null, $month);

        if ($year) {
            $previousYear = (string)($year - 1);

            $currentTotalCount = $query->clone()
                ->whereHas('eventCategories', function ($query) use ($year) {
                    $query->whereYear('start_date', '=', $year);
                })->count();

            $previousTotalCount = $query->clone()
                ->whereHas('eventCategories', function ($query) use ($previousYear) {
                    $query->whereYear('start_date', '=', $previousYear);
                })->count();

            return (new PercentageChange)->calculate($currentTotalCount, $previousTotalCount);

        } elseif ($period) {
            $currentPeriod = $period->toCarbonInstance();
            $previousPeriod = $period->toCarbonInstance(true);
        } else {
            $currentPeriod = now()->subDays(self::getPercentChangeDays());
            $previousPeriod = $currentPeriod->copy()->subDays(self::getPercentChangeDays());
        }

        $currentTotalCount = $query->clone()
            ->whereHas('eventCategories', function ($query) use ($currentPeriod) {
                $query->where('start_date', '>=', $currentPeriod);
            })->count();

        $previousTotalCount = $query->clone()
            ->whereHas('eventCategories', function ($query) use ($currentPeriod, $previousPeriod) {
                $query->where('start_date', '>=', $previousPeriod)
                    ->where('start_date', '<', $currentPeriod);
            })->count();

        return (new PercentageChange)->calculate($currentTotalCount, $previousTotalCount);
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param string|null $status
     * @param string|null $category
     * @param int|null $year
     * @param TimePeriodReferenceService|null $period
     * @return \array[][]
     */
    protected static function eventsStatsData(?StatisticsEntityEnum $entity, ?string $status, ?string $category, ?int $year, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => EventStatisticsTypeEnum::Events->name,
            'total' => FormatNumber::format(self::eventsSummaryQuery($entity, $status, $category, $year, null, $period)->count()),
            'percent_change' => self::eventsSummaryPercentChange($entity, $status, $category, $year, null, $period),
            'type_param_value' => EventStatisticsTypeEnum::Events->value
        ];
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param string|null $status
     * @param string|null $category
     * @param int|null $year
     * @param int|null $month
     * @param TimePeriodReferenceService|null $period
     * @return Collection|\Illuminate\Support\Collection|array
     */
    protected static function eventsStackedChartData(?StatisticsEntityEnum $entity = null, ?string $status = null, ?string $category = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): Collection|\Illuminate\Support\Collection|array
    {
        $states = $status ? [EventStateEnum::tryFrom($status)] : EventStateEnum::cases();

        return collect($states)->map(function ($state) use ($entity, $category, $year, $month, $period) {
            return [
                'name' => $state->name,
                'total' => self::eventsSummaryQuery($entity, $state->value, $category, $year, $month, $period)->count()
            ];
        });
    }
}
