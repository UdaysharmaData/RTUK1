<?php

namespace App\Services\Reporting\Traits;

use App\Services\PercentageChange;
use App\Services\TimePeriodReferenceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Enums\CharityMembershipTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HigherOrderWhenProxy;
use App\Http\Helpers\FormatNumber;

use App\Services\Reporting\Traits\PercentageChangeTrait;

use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Enums\ExternalEnquiryStatisticsTypeEnum;

use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\CharityMembership;

trait CharityStatsTrait
{
    use PercentageChangeTrait;

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param string|null $status
     * @param string|null $category
     * @param int|null $year
     * @param int|null $month
     * @param TimePeriodReferenceService|null $period
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function charitiesSummaryQuery(?StatisticsEntityEnum $entity = null, ?string $status = null, ?string $category = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): Builder|HigherOrderWhenProxy|null
    {
        return Charity::when($status,
                fn($query) => $query->whereHas('charityMemberships', function ($query) use ($status, $year, $month, $period) {
                    $query->where('type', CharityMembershipTypeEnum::from($status))
                        ->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
                        ->when($month, fn($query) => $query->whereMonth('created_at', '=', $month))
                        ->when($period, fn($query) => $query->where('created_at', '>=', $period->toCarbonInstance()));
                }),
                fn($query) => $query->whereHas('charityMemberships', function ($query) use ($year, $month, $period) {
                    $query->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
                        ->when($month, fn($query) => $query->whereMonth('created_at', '=', $month))
                        ->when($period, fn($query) => $query->where('created_at', '>=', $period->toCarbonInstance()));
                })
            )
            ->when($entity == StatisticsEntityEnum::Enquiry, fn($query) => $query->whereHas('enquiries', function ($query) use ($year, $month, $period) {
                $query->where('site_id', clientSiteId())
                    ->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
                    ->when($month, fn($query) => $query->whereMonth('created_at', '=', $month))
                    ->when($period, fn($query) => $query->where('created_at', '>=', $period->toCarbonInstance()));
            }))
            ->when($entity == StatisticsEntityEnum::ExternalEnquiry, fn($query) => $query->whereHas('externalEnquiries', function ($query) use ($year, $month, $period) {
                $query->where('site_id', clientSiteId())
                    ->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
                    ->when($month, fn($query) => $query->whereMonth('created_at', '=', $month))
                    ->when($period, fn($query) => $query->where('created_at', '>=', $period->toCarbonInstance()));
            }))
            ->when($category, fn($query) => $query->whereHas('charityCategory', function ($query) use ($category) {
                $query->where('ref', $category);
            }));
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
    public static function charitiesSummaryPercentChange(?StatisticsEntityEnum $entity = null, ?string $status = null, ?string $category = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): float
    {
        $query = self::charitiesSummaryQuery($entity, $status, $category, null, $month);

        if ($year) {
            $previousYear = (string)($year - 1);

            $currentTotalCount = $query->clone()
                ->whereYear('created_at', '=', $year)
                ->count();

            $previousTotalCount = $query->clone()
                ->whereYear('created_at', '=', $previousYear)
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
            ->where('created_at', '>=', $currentPeriod)
            ->count();

        $previousTotalCount = $query->clone()
            ->where('created_at', '>=', $previousPeriod)
            ->where('created_at', '<', $currentPeriod)
            ->count();

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
    protected static function charitiesStatsData(?StatisticsEntityEnum $entity, ?string $status, ?string $category, ?int $year, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => ExternalEnquiryStatisticsTypeEnum::Charities->name,
            'total' => FormatNumber::format(self::charitiesSummaryQuery($entity, $status, $category, $year, null, $period)->count()),
            'percent_change' => self::charitiesSummaryPercentChange($entity, $status, $category, $year, null, $period),
            'type_param_value' => ExternalEnquiryStatisticsTypeEnum::Charities->value
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
    protected static function charitiesStackedChartData(?StatisticsEntityEnum $entity = null, ?string $status = null, ?string $category = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): Collection|\Illuminate\Support\Collection|array
    {
        return CharityMembership::query()
            ->select(['type'])
            ->when($status, fn($query) => $query->where('type', $status))
            ->distinct()
            ->get()
            ->map(function ($membership) use ($entity, $category, $year, $month, $period) {
                $item = [];
                $item['name'] = $membership->type->name;
                $item['total'] = self::charitiesSummaryQuery($entity, $membership->type?->value, $category, $year, $month, $period)->count();
                return $item;
            });
    }
}
