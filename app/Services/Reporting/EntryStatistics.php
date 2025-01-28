<?php

namespace App\Services\Reporting;

use App\Modules\Setting\Enums\OrganisationEnum;
use App\Enums\TimeReferenceEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

use App\Modules\Setting\Enums\SiteEnum;
use App\Facades\ClientOptions;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Enums\EntryStatisticsTypeEnum;

use App\Traits\SiteTrait;
use App\Services\Charting\StackedAreaChart;
use App\Services\TimePeriodReferenceService;
use App\Services\ClientOptions\InvoiceOptions;
use App\Services\Reporting\Traits\OptionsTrait;
use App\Services\ClientOptions\ParticipantOptions;
use App\Services\Reporting\Traits\EntryStatsTrait;
use App\Services\ClientOptions\EventCategoryOptions;
use App\Services\Reporting\Traits\InvoiceStatsTrait;
use App\Services\Reporting\Traits\AnalyticsStatsTrait;
use App\Services\Reporting\Traits\EventCategoryStatsTrait;
use App\Services\Reporting\Traits\StructureChartDataTrait;
use App\Modules\Setting\Models\Traits\SiteQueryScopeTrait;

class EntryStatistics
{
    use SiteQueryScopeTrait, OptionsTrait, StructureChartDataTrait, AnalyticsStatsTrait, InvoiceStatsTrait, EntryStatsTrait, SiteTrait, EventCategoryStatsTrait;

    const ENTITY = StatisticsEntityEnum::Entry;

    /**
     * @param  string|null  $type
     * @param  int|null     $year
     * @param  string|null  $status
     * @param  string|null  $category
     * @param  string|null  $period
     * @return array
     */
    public static function generateStatsSummary(?string $type = null, ?int $year = null, ?string $status = null, ?string $category = null, ?string $period = null): array
    {
        if (is_null(TimeReferenceEnum::tryFrom($period)?->value) || $period === TimeReferenceEnum::All->value) {
            $timePeriodReferenceService = null;
        } else $timePeriodReferenceService = new TimePeriodReferenceService($period);

        $userId = request()->user()?->id;

        if ($type === EntryStatisticsTypeEnum::Entries->value) {
            $data = [
                'stats' => [self::entriesStatsData(self::ENTITY, $year, $status, $category, null, $userId, $timePeriodReferenceService)]
            ];
        } elseif ($type === EntryStatisticsTypeEnum::Invoices->value) {
            $data = [
                'stats' => [self::invoicesStatsData(self::ENTITY, $year, $status, $category, $userId, $timePeriodReferenceService)]
            ];
        } else {
            $data = [
                'stats' => [
                    self::entriesStatsData(self::ENTITY, $year, $status, $category, null, $userId, $timePeriodReferenceService),
                ]
            ];

            if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
                $data['stats'][] = self::invoicesStatsData(self::ENTITY, $year, $status, $category, $userId, $timePeriodReferenceService);
            }
        }

        return array_merge($data, self::getOptions());
    }

    /**
     * @param string $type
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param string|null $period
     * @return array
     */
    public static function generateYearGraphData(string $type, ?int $year, ?string $status, ?string $category, ?string $period = null): array
    {
        if (is_null(TimeReferenceEnum::tryFrom($period)?->value) || $period === TimeReferenceEnum::All->value) {
            $timePeriodReferenceService = null;
        } else $timePeriodReferenceService = new TimePeriodReferenceService($period);

        $data = [];
        $userId = request()->user()?->id;
        $months = self::months();

        if ($type == EntryStatisticsTypeEnum::Entries->value) {
            foreach ($months as $key => $value) {
                $data[] = [
                    'month' => $key,
                    'categories' => self::participantsStackedAreaChartData(self::ENTITY, $year, $status, $category, $value, $userId, $timePeriodReferenceService)
                ];
            }

            $data = (new StackedAreaChart)->format($data);

        } elseif ($type == EntryStatisticsTypeEnum::Invoices->value) {
            foreach ($months as $key => $value) {
                $data[] = [
                    'month' => $key,
                    'categories' => self::invoicesStackedChartData(self::ENTITY, $status, $category, $year, $value, $userId, $timePeriodReferenceService)
                ];
            }

            $data = (new StackedAreaChart)->format($data);
        }

        return $data;
    }

    /**
     * @param bool $required
     * @return \Illuminate\Validation\Validator
     */
    public static function getParamsValidator(bool $required = true): \Illuminate\Validation\Validator
    {
        return Validator::make(request()->all(), [
            'type' => [
                $required ? 'required' : 'sometimes',
                new Enum(EntryStatisticsTypeEnum::class)
            ],
            'period' => [
                'sometimes', Rule::in(TimeReferenceEnum::values())
            ]
        ]);
    }

    /**
     * @param  mixed  $type
     * @param  mixed  $year
     * @param  mixed  $category
     * @param  mixed  $period
     * @return array
     */
    public static function setParams(mixed $type, mixed $year, mixed $category = null, mixed $period): array
    {
        $period = TimeReferenceEnum::tryFrom($period)?->value;

        if ($type == EntryStatisticsTypeEnum::Entries->value) {
            $category = in_array($category, EventCategoryOptions::getRefOptions('entries')->pluck('value')->toArray()) ? $category : null;
            $year = in_array($year, ParticipantOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        } elseif ($type == EntryStatisticsTypeEnum::Invoices->value) {
            $year = in_array($year, InvoiceOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
            $category = null;
        } else {
            $category = null;
            $year = null;
        }
        return array($year, $category, $period);
    }

    /**
     * @return \array[][]
     */
    public static function getOptions(): array
    {
        $options = [
            EntryStatisticsTypeEnum::Entries->value => ClientOptions::only('entries', [
                'categories',
                'years',
                'periods'
            ])
        ];

        if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
            $options[EntryStatisticsTypeEnum::Invoices->value] = [
                'years' => InvoiceOptions::getYearOptions(),
                'periods' => self::getPeriodOptions()
            ];
        }

        return [
            'types' => EntryStatisticsTypeEnum::_options(),
            'options' => $options
        ];
    }
}
