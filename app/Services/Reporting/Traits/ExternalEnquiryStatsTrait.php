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
use App\Enums\ExternalEnquiryStatusEnum;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Services\Reporting\Traits\PercentageChangeTrait;
use App\Services\Reporting\Enums\ExternalEnquiryStatisticsTypeEnum;

use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Partner\Models\PartnerChannel;

trait ExternalEnquiryStatsTrait
{
    use PercentageChangeTrait;

    /**
     * @param string|null $status
     * @param int|null $year
     * @param int|null $month
     * @param TimePeriodReferenceService|null $period
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function externalEnquiriesSummaryQuery(?string $status = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): Builder|HigherOrderWhenProxy|null
    {
        return ExternalEnquiry::query()
                ->where('site_id', clientSiteId())
                ->when($status, fn($query) => $query->status(ExternalEnquiryStatusEnum::tryFrom($status)))
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
    public static function externalEnquiriesSummaryPercentChange(?string $status = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): float
    {
        $query = self::externalEnquiriesSummaryQuery($status, null, $month);

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
    protected static function externalEnquiriesStatsData(?string $status, ?int $year, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => ExternalEnquiryStatisticsTypeEnum::Enquiries->name,
            'total' => FormatNumber::format(self::externalEnquiriesSummaryQuery($status, $year, null, $period)->count()),
            'percent_change' => self::externalEnquiriesSummaryPercentChange($status, $year, null, $period),
            'type_param_value' => ExternalEnquiryStatisticsTypeEnum::Enquiries->value
        ];
    }

    /**
     * @param string|null $status
     * @param int|null $year
     * @param int|null $month
     * @param TimePeriodReferenceService|null $period
     * @return Collection|\Illuminate\Support\Collection|array
     */
    protected static function externalEnquiriesStackedChartData(?string $status = null, ?int $year = null, ?int $month = null, ?TimePeriodReferenceService $period = null): Collection|\Illuminate\Support\Collection|array
    {
        return PartnerChannel::query()
            ->whereHas('partner', function ($query) {
                $query->where('site_id', clientSiteId());
            })->select(['name'])
            ->selectSub(function (\Illuminate\Database\Query\Builder $query) use ($status, $year, $month, $period) {
                $query->selectRaw('COUNT(*)')
                    ->from('external_enquiries')
                    ->whereColumn('partner_channels.id', '=', 'external_enquiries.partner_channel_id')
                    ->whereNull('external_enquiries.deleted_at')
                    ->where('site_id', clientSiteId())
                    ->when($year, fn($query) => $query->whereYear('external_enquiries.created_at', '=', $year))
                    ->when($status == ExternalEnquiryStatusEnum::Processed->value, fn($query) => $query->whereNotNull('participant_id'))
                    ->when($status == ExternalEnquiryStatusEnum::Pending->value, fn($query) => $query->whereNull('participant_id'))
                    ->when($month, fn($query) => $query->whereMonth('external_enquiries.created_at', '=', $month))
                    ->when($period, fn($query) => $query->where('external_enquiries.created_at', '>=', $period->toCarbonInstance()));
            }, 'total')
            ->get()
            ->map(function ($category) {
                $item = [];
                $item['name'] = $category->name;
                $item['total'] = $category->total;
                return $item;
            });
    }
}
