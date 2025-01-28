<?php

namespace App\Services\Reporting;

use App\Enums\TimeReferenceEnum;
use App\Services\Charting\StackedAreaChart;
use App\Services\Charting\StackedColumnChart;
use App\Services\TimePeriodReferenceService;
use Illuminate\Validation\Rule;
use App\Modules\Event\Models\Event;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\EventStateEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Modules\Setting\Enums\OrganisationEnum;
use App\Enums\ParticipantStatusEnum;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Enums\EventStatisticsTypeEnum;

use App\Services\ClientOptions\ParticipantOptions;
use App\Services\ClientOptions\EventCategoryOptions;

use App\Traits\SiteTrait;
use App\Services\Reporting\Traits\OptionsTrait;
use App\Services\Reporting\Traits\EventStatsTrait;
use App\Services\Reporting\Traits\InvoiceStatsTrait;
use App\Services\Reporting\Traits\AnalyticsStatsTrait;
use App\Services\Reporting\Traits\ParticipantStatsTrait;
use App\Services\Reporting\Traits\StructureChartDataTrait;
use App\Modules\Setting\Models\Traits\SiteQueryScopeTrait;
use App\Services\Reporting\Traits\EventCategoryStatsTrait;

use App\Services\ClientOptions\EventOptions;
use App\Services\ClientOptions\InvoiceOptions;

class EventStatistics
{
    use SiteQueryScopeTrait, OptionsTrait, StructureChartDataTrait, AnalyticsStatsTrait, ParticipantStatsTrait, InvoiceStatsTrait, EventStatsTrait, EventCategoryStatsTrait, SiteTrait;

    const ENTITY = StatisticsEntityEnum::Event;

    /**
     * @param string|null $type
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param string|null $period
     * @return array
     */
    public static function generateStatsSummary(?string $type = null, ?int $year = null, ?string $status = null, ?string $category = null, ?string $period = null): array
    {
        if (is_null(TimeReferenceEnum::tryFrom($period)?->value) || $period === TimeReferenceEnum::All->value) {
            $timePeriodReferenceService = null;
        } else $timePeriodReferenceService = new TimePeriodReferenceService($period);

        if ($type === EventStatisticsTypeEnum::Events->value) {
            $data = [
                'stats' => [self::eventsStatsData(self::ENTITY, $status, $category, $year, $timePeriodReferenceService)]
            ];
        } elseif ($type === EventStatisticsTypeEnum::Participants->value) {
            $data = [
                'stats' => [self::participantsStatsData(self::ENTITY, $year, $status, $category, null, $timePeriodReferenceService)]
            ];
        } elseif ($type === EventStatisticsTypeEnum::Invoices->value) {
            $data = [
                'stats' => [self::invoicesStatsData(self::ENTITY, $year, $status, $category, null, $timePeriodReferenceService)]
            ];
        } else {
            $data = [
                'stats' => [
                    self::eventsStatsData(self::ENTITY, $status, $category, $year, $timePeriodReferenceService),
                    self::participantsStatsData(self::ENTITY, $year, $status, $category, null, $timePeriodReferenceService),
                ]
            ];

            if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
                $data['stats'][] = self::invoicesStatsData(self::ENTITY, $year, $status, $category, null, $timePeriodReferenceService);
            }
        }

        $data['stats'] = array_merge($data['stats'], self::getAnalytics(Event::class));

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
        $months = self::months();

        if ($type == EventStatisticsTypeEnum::Participants->value) {
            foreach ($months as $key => $value) {
                $data[] = [
                    'month' => $key,
                    'categories' => self::participantsStackedAreaChartData(self::ENTITY, $year, $status, $category, $value, null, $timePeriodReferenceService)
                ];
            }

            $data = (new StackedAreaChart)->format($data);

        } elseif ($type == EventStatisticsTypeEnum::Events->value) {
            foreach ($months as $key => $value) {
                $data[] = [
                    'month' => $key,
                    'categories' => self::eventsStackedChartData(self::ENTITY, $status, $category, $year, $value, $timePeriodReferenceService)
                ];
            }

            $data = (new StackedColumnChart)->format($data);

        } elseif ($type == EventStatisticsTypeEnum::Invoices->value) {
            foreach ($months as $key => $value) {
                $data[] = [
                    'month' => $key,
                    'categories' => self::invoicesStackedChartData(self::ENTITY, $status, $category, $year, $value, null, $timePeriodReferenceService)
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
                new Enum(EventStatisticsTypeEnum::class)
            ],
            'period' => [
                'sometimes', Rule::in(TimeReferenceEnum::values())
            ]
        ]);
    }

    /**
     * @param mixed $type
     * @param mixed $status
     * @param mixed $category
     * @param mixed $year
     * @param mixed $period
     * @return array
     */
    public static function setParams(mixed $type, mixed $status, mixed $category = null, mixed $year, mixed $period): array
    {
        $period = TimeReferenceEnum::tryFrom($period)?->value;

        if ($type == EventStatisticsTypeEnum::Participants->value) {
            $status = ParticipantStatusEnum::tryFrom($status)?->value;
            $year = in_array($year, ParticipantOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        } elseif ($type == EventStatisticsTypeEnum::Invoices->value) {
            $status = InvoiceStatusEnum::tryFrom($status)?->value;
            $year = in_array($year, InvoiceOptions::getInvoiceItemYearOptions()->pluck('value')->toArray()) ? $year : null;
        } elseif ($type == EventStatisticsTypeEnum::Events->value) {
            $category = in_array($category, EventCategoryOptions::getRefOptions()->pluck('value')->toArray()) ? $category : null;
            $status = EventStateEnum::tryFrom($status)?->value;
            $year = in_array($year, EventOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        } else {
            $category = null;
            $status = null;
            $year = null;
        }
        return array($status, $year, $category, $period);
    }

    /**
     * @return \array[][]
     */
    public static function getOptions(): array
    {
        $options = [
            EventStatisticsTypeEnum::Events->value => [
                'categories' => EventCategoryOptions::getRefOptions(),
                'statuses' => EventOptions::getStateOptions(),
                'years' => EventOptions::getYearOptions(),
            ],
            EventStatisticsTypeEnum::Participants->value => [
                'statuses' => ParticipantStatusEnum::_options(),
                'years' => ParticipantOptions::getYearOptions(),
                'periods' => self::getPeriodOptions()
            ]
        ];

        if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
            $options[EventStatisticsTypeEnum::Invoices->value] = [
                'categories' => InvoiceItemTypeEnum::_options(),
                'statuses' => InvoiceStatusEnum::_options(),
                'years' => InvoiceOptions::getInvoiceItemYearOptions(),
                'periods' => self::getPeriodOptions()
            ];
        }

        return [
            'types' => EventStatisticsTypeEnum::_options(),
            'options' => $options
        ];
    }
}
