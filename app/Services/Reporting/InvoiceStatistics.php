<?php

namespace App\Services\Reporting;

use App\Models\Invoice;
use App\Facades\ClientOptions;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

use App\Enums\EventStateEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\TimeReferenceEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Enums\ParticipantStatusEnum;

use App\Services\TimePeriodReferenceService;
use App\Services\Charting\StackedColumnChart;
use App\Services\ClientOptions\InvoiceOptions;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Enums\InvoiceStatisticsTypeEnum;

use App\Services\Reporting\Traits\OptionsTrait;
use App\Services\Reporting\Traits\EventStatsTrait;
use App\Services\Reporting\Traits\InvoiceStatsTrait;
use App\Services\Reporting\Traits\AnalyticsStatsTrait;
use App\Services\Reporting\Traits\ParticipantStatsTrait;
use App\Services\Reporting\Traits\StructureChartDataTrait;
use App\Modules\Setting\Models\Traits\SiteQueryScopeTrait;
use App\Services\Reporting\Traits\EventCategoryStatsTrait;

class InvoiceStatistics
{
    use SiteQueryScopeTrait, OptionsTrait, StructureChartDataTrait, AnalyticsStatsTrait, ParticipantStatsTrait, InvoiceStatsTrait, EventStatsTrait, EventCategoryStatsTrait;

    const ENTITY = StatisticsEntityEnum::Invoice;

    /**
     * @param string|null $type
     * @param int|null $year
     * @param string|null $status
     * @param string|null $period
     * @return array
     */
    public static function generateStatsSummary(?string $type = null, ?int $year = null, ?string $status = null, ?string $period = null): array
    {
        if (is_null(TimeReferenceEnum::tryFrom($period)?->value) || $period === TimeReferenceEnum::All->value) {
            $timePeriodReferenceService = null;
        } else $timePeriodReferenceService = new TimePeriodReferenceService($period);

        if ($type === InvoiceStatisticsTypeEnum::Invoices->value) {
            $data = [
                'stats' => [self::invoicesStatsData(self::ENTITY, $year, $status, null, null, $timePeriodReferenceService)]
            ];
        } elseif (InvoiceStatisticsTypeEnum::tryFrom($type)) {
            $data = [
                'stats' => [self::invoicesStatsData(self::ENTITY, $year, $status, InvoiceStatisticsTypeEnum::from($type)->value, null, $timePeriodReferenceService)]
            ];
        } else {
            $data['stats'] = InvoiceStatisticsTypeEnum::_options()->map(function ($item, $key) use ($year, $status, $timePeriodReferenceService) {
                return self::invoicesStatsData(self::ENTITY, $year, $status, InvoiceItemTypeEnum::tryFrom($item['value'])?->value, null, $timePeriodReferenceService);
            })->toArray();
        }

        return array_merge($data, self::getOptions());
    }

    /**
     * @param string $type
     * @param int|null $year
     * @param string|null $status
     * @param string|null $period
     * @return array
     */
    public static function generateYearGraphData(string $type, ?int $year, ?string $status, ?string $period = null): array
    {
        if (is_null(TimeReferenceEnum::tryFrom($period)?->value) || $period === TimeReferenceEnum::All->value) {
            $timePeriodReferenceService = null;
        } else $timePeriodReferenceService = new TimePeriodReferenceService($period);

        $data = [];

        $months = self::months();

        if ($type == InvoiceStatisticsTypeEnum::Invoices->value) {
            foreach ($months as $key => $value) {
                $data[] = [
                    'month' => $key,
                    'categories' => self::invoicesStackedChartData(self::ENTITY, $status, null, $year, $value, null, $timePeriodReferenceService)
                ];
            }

            $data = (new StackedColumnChart)->format($data);

        } elseif (InvoiceStatisticsTypeEnum::tryFrom($type)) {
            foreach ($months as $key => $value) {
                $data[] = [
                    'month' => $key,
                    'categories' => self::invoicesStackedChartData(self::ENTITY, $status, InvoiceStatisticsTypeEnum::from($type)->value, $year, $value, null, $timePeriodReferenceService)
                ];
            }

            $data = (new StackedColumnChart)->format($data);
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
                new Enum(InvoiceStatisticsTypeEnum::class)
            ],
            'period' => [
                'sometimes', Rule::in(TimeReferenceEnum::values())
            ]
        ]);
    }

    /**
     * @param mixed $type
     * @param mixed $status
     * @param mixed $year
     * @param mixed $period
     * @return array
     */
    public static function setParams(mixed $type, mixed $status, mixed $year, mixed $period): array
    {
        $period = TimeReferenceEnum::tryFrom($period)?->value;

        if (InvoiceStatisticsTypeEnum::tryFrom($type) && $type != InvoiceStatisticsTypeEnum::Invoices->value) {
            $status = InvoiceStatusEnum::tryFrom($status)?->value;
            $year = in_array($year, InvoiceOptions::getInvoiceItemYearOptions(InvoiceItemTypeEnum::tryFrom($type))->pluck('value')->toArray()) ? $year : null;
        } else {
            $status = InvoiceStatusEnum::tryFrom($status)?->value;
            $year = in_array($year, InvoiceOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        }

        return array($status, $year, $period);
    }

    /**
     * @return \array[][]
     */
    public static function getOptions(): array
    {
        return [
            'types' => InvoiceStatisticsTypeEnum::_options(),
            'options' => [
                InvoiceStatisticsTypeEnum::Invoices->value => [
                    ...ClientOptions::only('invoices', [
                        'years',
                        'statuses',
                        'periods'
                    ])
                ],
                InvoiceStatisticsTypeEnum::ParticipantRegistration->value => [
                    'years' => InvoiceOptions::getInvoiceItemYearOptions(InvoiceItemTypeEnum::ParticipantRegistration),
                    ...ClientOptions::only('invoices', [
                        'statuses',
                        'periods'
                    ])
                ],
                InvoiceStatisticsTypeEnum::EventPlaces->value => [
                    'years' => InvoiceOptions::getInvoiceItemYearOptions(InvoiceItemTypeEnum::EventPlaces),
                    ...ClientOptions::only('invoices', [
                        'statuses',
                        'periods'
                    ])
                ],
                InvoiceStatisticsTypeEnum::MarketResale->value => [
                    'years' => InvoiceOptions::getInvoiceItemYearOptions(InvoiceItemTypeEnum::MarketResale),
                    ...ClientOptions::only('invoices', [
                        'statuses',
                        'periods'
                    ])
                ],
                InvoiceStatisticsTypeEnum::CharityMembership->value => [
                    'years' => InvoiceOptions::getInvoiceItemYearOptions(InvoiceItemTypeEnum::CharityMembership),
                    ...ClientOptions::only('invoices', [
                        'statuses',
                        'periods'
                    ])
                ],
                InvoiceStatisticsTypeEnum::PartnerPackageAssignment->value => [
                    'years' => InvoiceOptions::getInvoiceItemYearOptions(InvoiceItemTypeEnum::PartnerPackageAssignment),
                    ...ClientOptions::only('invoices', [
                        'statuses',
                        'periods'
                    ])
                ],
                InvoiceStatisticsTypeEnum::CorporateCredit->value => [
                    'years' => InvoiceOptions::getInvoiceItemYearOptions(InvoiceItemTypeEnum::CorporateCredit),
                    ...ClientOptions::only('invoices', [
                        'statuses',
                        'periods'
                    ])
                ]
            ]
        ];
    }
}
