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
use App\Enums\EnquiryStatusEnum;
use App\Modules\Enquiry\Models\Enquiry;
use App\Services\Reporting\Traits\PercentageChangeTrait;
use App\Services\Reporting\Enums\EnquiryStatisticsTypeEnum;

use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Partner\Models\PartnerChannel;

trait EnquiryStatsTrait
{
    use PercentageChangeTrait;

    /**
     * @param string|null $status
     * @param int|null $year
     * @param int|null $month
     * @param TimePeriodReferenceService|null $period
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function enquiriesSummaryQuery(?string $status = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): Builder|HigherOrderWhenProxy|null
    {
        return Enquiry::query()
                ->where('site_id', clientSiteId())
                ->when($status, fn($query) => $query->status(EnquiryStatusEnum::tryFrom($status)))
                ->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
                ->when($month, fn($query) => $query->whereMonth('created_at', '=', $month))
                ->when($period, fn($query) => $query->where('created_at', '>=', $period->toCarbonInstance()));
    }

    /**
     * @param string|null $status
     * @param int|null $year
     * @param int|null $month
     * @param TimePeriodReferenceService|null $period
     * @return float
     */
    public static function enquiriesSummaryPercentChange(?string $status = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): float
    {
        $query = self::enquiriesSummaryQuery($status, null, $month);

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
     * @param string|null $status
     * @param int|null $year
     * @param TimePeriodReferenceService|null $period
     * @return \array[][]
     */
    protected static function enquiriesStatsData(?string $status, ?int $year, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => EnquiryStatisticsTypeEnum::Enquiries->name,
            'total' => FormatNumber::format(self::enquiriesSummaryQuery($status, $year, null, $period)->count()),
            'percent_change' => self::enquiriesSummaryPercentChange($status, $year, null, $period),
            'type_param_value' => EnquiryStatisticsTypeEnum::Enquiries->value
        ];
    }

    /**
     * @param string|null $status
     * @param int|null $year
     * @param int|null $month
     * @param TimePeriodReferenceService|null $period
     * @return Collection|\Illuminate\Support\Collection|array
     */
    protected static function enquiriesStackedChartData(?string $status = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): Collection|\Illuminate\Support\Collection|array
    {
        $categories = $status ? [EnquiryStatusEnum::tryFrom($status)] : EnquiryStatusEnum::cases();

        return collect($categories)->map(function ($status) use ($year, $month, $period) {
                return [
                    'name' => $status->name,
                    'total' => self::enquiriesSummaryQuery($status->value, $year, $month, $period)->count()
                ];
            });
    }
}
