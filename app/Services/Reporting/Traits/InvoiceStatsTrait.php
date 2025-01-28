<?php

namespace App\Services\Reporting\Traits;

use App\Modules\Finance\Models\InternalTransaction;
use App\Services\PercentageChange;
use App\Services\TimePeriodReferenceService;
use Auth;
use App\Http\Helpers\AccountType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Reporting\Traits\PercentageChangeTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HigherOrderWhenProxy;
use App\Http\Helpers\FormatNumber;

use App\Enums\InvoiceStatusEnum;
use App\Enums\CharityUserTypeEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Enums\InvoiceStatisticsTypeEnum;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\ResaleRequest;
use App\Modules\Participant\Models\Participant;
use App\Modules\Charity\Models\CharityMembership;
use App\Modules\Charity\Models\EventPlaceInvoice;
use App\Modules\Charity\Models\CharityPartnerPackage;

trait InvoiceStatsTrait
{
    use PercentageChangeTrait, OptionsTrait;

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @param int|null $month
     * @return Builder|HigherOrderWhenProxy|null
     */
    public static function invoicesSummaryQuery(?StatisticsEntityEnum $entity = null, ?int $year = null, ?string $status = null, ?string $category = null, ?int $userId = null, ?TimePeriodReferenceService $period = null, ?int $month = null): Builder|HigherOrderWhenProxy|null
    {
        if ($entity == StatisticsEntityEnum::Event || $entity == StatisticsEntityEnum::Participant) {
            $query = self::invoiceItemQuery($entity, InvoiceStatusEnum::tryFrom($status), InvoiceItemTypeEnum::ParticipantRegistration, $year, $month, $userId, $period);
        } else if ($category) {
            $query = self::invoiceItemQuery($entity, InvoiceStatusEnum::tryFrom($status), InvoiceItemTypeEnum::tryFrom($category), $year, $month, $userId, $period);
        } else {
            $query = self::invoiceQuery($entity, InvoiceStatusEnum::tryFrom($status), $year, $month, $userId, $period);
        }

        return $query;
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return float
     */
    public static function invoicesSummaryPercentChange(?StatisticsEntityEnum $entity = null, ?int $year = null, ?string $status = null, ?string $category = null, ?int $userId = null, ?TimePeriodReferenceService $period = null): float
    {
        $query = self::invoicesSummaryQuery($entity, null, $status, $category, $userId);

        if ($year) {
            $previousYear = (string)($year - 1);

            $currentTotalCount = $query->clone()
                ->whereYear('created_at', '=', $year)
                ->sum('price');

            $previousTotalCount = $query->clone()
                ->whereYear('created_at', '=', $previousYear)
                ->sum('price');

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
            ->sum('price');

        $previousTotalCount = $query->clone()
            ->where('created_at', '>=', $previousPeriod)
            ->where('created_at', '<', $currentPeriod)
            ->sum('price');

        return (new PercentageChange)->calculate($currentTotalCount, $previousTotalCount);
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return \array[][]
     */
    protected static function invoicesStatsData(?StatisticsEntityEnum $entity, ?int $year, ?string $status, ?string $category = null, ?int $userId = null, ?TimePeriodReferenceService $period = null): array
    {
        return [
            'name' => InvoiceStatisticsTypeEnum::tryFrom($category)?->formattedName() ?? InvoiceStatisticsTypeEnum::Invoices->formattedName(),
            'total' => FormatNumber::formatWithCurrency(self::invoicesSummaryQuery($entity, $year, $status, $category, $userId, $period)->sum('price')),
            'percent_change' => self::invoicesSummaryPercentChange($entity, $year, $status, $category, $userId, $period),
            'type_param_value' => InvoiceStatisticsTypeEnum::tryFrom($category)?->value ?? InvoiceStatisticsTypeEnum::Invoices->value
        ];
    }

    /**
     * @param StatisticsEntityEnum|null $entity
     * @param string|null $status
     * @param string|null $category
     * @param int|null $year
     * @param int|null $month
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return Collection|\Illuminate\Support\Collection|array
     */
    protected static function invoicesStackedChartData(?StatisticsEntityEnum $entity = null, ?string $status = null, ?string $category = null, ?int $year = null, ?int $month = null, ?int $userId = null, ?TimePeriodReferenceService $period = null): Collection|\Illuminate\Support\Collection|array
    {
        return Invoice::query()
            ->where('site_id', '=', clientSiteId())
            ->select(['status'])
            ->when($status, fn($query) => $query->where('status', '=', $status))
            ->distinct()
            ->get()
            ->map(function ($invoice) use ($entity, $category, $year, $month, $userId, $period) {
                $item = [];
                $item['name'] = $invoice->status->name;
                $item['total'] = self::invoicesSummaryQuery($entity, $year, $invoice->status->value, InvoiceItemTypeEnum::tryFrom($category)?->value, $userId, $period, $month)->sum('price');
                return $item;
            });
    }

    /**
     * Get the invoices
     *
     * @param StatisticsEntityEnum|null $entity
     * @param InvoiceStatusEnum|null $status
     * @param int|null $year
     * @param int|null $month
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return Builder
     */
    protected static function invoiceQuery(?StatisticsEntityEnum $entity = null, ?InvoiceStatusEnum $status = null, ?int $year = null, ?int $month = null, ?int $userId = null, ?TimePeriodReferenceService $period = null): Builder
    {
        return Invoice::query()
            ->where('site_id', '=', clientSiteId())
            ->when($userId, function(Builder $query) use ($userId) {
                $query->whereHasMorph(
                    'invoiceable',
                    User::class,
                    fn(Builder $query) => $query->where('invoiceable_id', '=', $userId)
                )->whereHas('invoiceItems');
            })
            ->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
            ->when($period, fn($query) => $query->where('created_at', '>=', $period->toCarbonInstance()))
            ->when($status, fn($query) => $query->where('status', '=', $status))
            ->when($month, fn($query) => $query->whereMonth('created_at', '=', $month))
            ->whereNull('deleted_at');
    }

    /**
     * Get the invoice items filtered by the type
     *
     * @param StatisticsEntityEnum|null $entity
     * @param InvoiceStatusEnum|null $status
     * @param InvoiceItemTypeEnum|null $category
     * @param int|null $year
     * @param int|null $month
     * @param int|null $userId
     * @param TimePeriodReferenceService|null $period
     * @return Builder
     */
    protected static function invoiceItemQuery(?StatisticsEntityEnum $entity = null, ?InvoiceStatusEnum $status = null, ?InvoiceItemTypeEnum $category = null, ?int $year = null, ?int $month = null, ?int $userId = null, ?TimePeriodReferenceService $period = null): Builder
    {
        return InvoiceItem::when($category == InvoiceItemTypeEnum::ParticipantRegistration, fn($query) => $query->whereHasMorph(
                'invoiceItemable',
                [Participant::class],
                fn(Builder $query) => $query->whereHas('eventEventCategory', function (Builder $query) {
                    $query->whereHas('eventCategory', function (Builder $query) {
                        $query->where('site_id', clientSiteId());
                    });
                })
            ))
            ->when($category == InvoiceItemTypeEnum::EventPlaces, fn($query) => $query->whereHasMorph(
                'invoiceItemable',
                [EventPlaceInvoice::class],
                function(Builder $query) {
                    if (AccountType::isCharityOwnerOrCharityUser()) {
                        $query->whereHas('charity.users', function ($query) {
                            $query->where('users.id', Auth::user()->id)
                                ->where(function($query) {
                                    $query->where('type', CharityUserTypeEnum::Owner)
                                        ->orWhere('type', CharityUserTypeEnum::User);
                                });
                        });
                    }
                }
            ))
            ->when($category == InvoiceItemTypeEnum::CharityMembership, fn($query) => $query->whereHasMorph(
                'invoiceItemable',
                [CharityMembership::class],
                // TODO: Complete this query
            ))
            ->when($category == InvoiceItemTypeEnum::PartnerPackageAssignment, fn($query) => $query->whereHasMorph(
                'invoiceItemable',
                [CharityPartnerPackage::class],
                // TODO: Complete this query
            ))
            ->when($category == InvoiceItemTypeEnum::MarketResale, fn($query) => $query->whereHasMorph(
                'invoiceItemable',
                [ResaleRequest::class],
                // TODO: Complete this query
            ))
            ->whereHas('invoice', function (Builder $query) use ($status) {
                $query->whereNull('deleted_at')
                    ->where('site_id', clientSiteId())
                    ->when($status, fn($query) => $query->where('status', '=', $status));
            })->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
            ->when($period, fn($query) => $query->where('created_at', '>=', $period->toCarbonInstance()))
            ->when($month, fn($query) => $query->whereMonth('created_at', '=', $month));
    }

    /**
     * @param  InvoiceItemTypeEnum|null  $type
     * @return mixed
     */
    public static function getInvoiceItemYearOptions(?InvoiceItemTypeEnum $type = null): mixed
    {
        return Cache::remember('invoice_item_stats_year_filter_options', now()->addMonth(), function () use ($type) {
            $years = InvoiceItem::query()
                ->selectRaw('DISTINCT YEAR(created_at) AS year')
                ->when($type, fn($query) => $query->where('type', $type))
                ->whereHas('invoice', function ($query) {
                    $query->where('site_id', clientSiteId());
                })
                ->whereNotNull('created_at')
                // ->orderByDesc('created_at')
                ->pluck('year')
                ->sortDesc();

            return $years->map(function ($option, $key) {
                return [
                    'label' => (string) $option,
                    'value' => $option
                ];
            })->values();
        });
    }


}
