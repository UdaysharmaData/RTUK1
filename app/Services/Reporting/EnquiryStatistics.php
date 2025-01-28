<?php

namespace App\Services\Reporting;

use App\Enums\TimeReferenceEnum;
use App\Services\Charting\StackedAreaChart;
use App\Services\Charting\StackedColumnChart;
use App\Services\TimePeriodReferenceService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\EventStateEnum;
use App\Enums\EnquiryStatusEnum;
use App\Enums\CharityMembershipTypeEnum;
use App\Modules\Setting\Enums\OrganisationEnum;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Enums\EnquiryStatisticsTypeEnum;

use App\Traits\SiteTrait;
use App\Services\Reporting\Traits\OptionsTrait;
use App\Services\Reporting\Traits\EventStatsTrait;
use App\Services\Reporting\Traits\CharityStatsTrait;
use App\Services\Reporting\Traits\EnquiryStatsTrait;
use App\Services\Reporting\Traits\AnalyticsStatsTrait;
use App\Services\Reporting\Traits\ParticipantStatsTrait;
use App\Services\Reporting\Traits\StructureChartDataTrait;
use App\Modules\Setting\Models\Traits\SiteQueryScopeTrait;

use App\Facades\ClientOptions;
use App\Services\ClientOptions\CharityOptions;
use App\Services\ClientOptions\EnquiryOptions;
use App\Services\ClientOptions\CharityCategoryOptions;

use App\Services\ClientOptions\EventOptions;
use App\Services\ClientOptions\EventCategoryOptions;

class EnquiryStatistics
{
    use SiteQueryScopeTrait, OptionsTrait, StructureChartDataTrait, AnalyticsStatsTrait, ParticipantStatsTrait, CharityStatsTrait, EnquiryStatsTrait, EventStatsTrait, SiteTrait;

    const ENTITY = StatisticsEntityEnum::Enquiry;

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

        if ($type === EnquiryStatisticsTypeEnum::Enquiries->value) {
            $data = [
                'stats' => [self::enquiriesStatsData($status, $year, $timePeriodReferenceService)]
            ];
        } elseif ($type === EnquiryStatisticsTypeEnum::Events->value) {
            $data = [
                'stats' => [self::eventsStatsData(self::ENTITY, $status, $category, $year, $timePeriodReferenceService)]
            ];
        } elseif ($type === EnquiryStatisticsTypeEnum::Charities->value) {
            $data = [
                'stats' => [self::charitiesStatsData(self::ENTITY, $status, $category, $year, $timePeriodReferenceService)]
            ];
        } else {
            $data = [
                'stats' => [
                    self::enquiriesStatsData($status, $year, $timePeriodReferenceService),
                    self::eventsStatsData(self::ENTITY, $status, $category, $year, $timePeriodReferenceService),
                ]
            ];

            if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
                $data['stats'][] = self::charitiesStatsData(self::ENTITY, $status, $category, $year, $timePeriodReferenceService);
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
        $months = self::months();

        if ($type == EnquiryStatisticsTypeEnum::Enquiries->value) {
            foreach ($months as $key => $value) {
                $data[] = [
                    'month' => $key,
                    'categories' => self::enquiriesStackedChartData($status, $year, $value, $timePeriodReferenceService)
                ];
            }

            $data = (new StackedColumnChart)->format($data);

        } elseif ($type == EnquiryStatisticsTypeEnum::Events->value) {
            foreach ($months as $key => $value) {
                $data[] = [
                    'month' => $key,
                    'categories' => self::eventsStackedChartData(self::ENTITY, $status, $category, $year, $value, $timePeriodReferenceService)
                ];
            }

            $data = (new StackedColumnChart)->format($data);

        } elseif ($type == EnquiryStatisticsTypeEnum::Charities->value) {
            foreach ($months as $key => $value) {
                $data[] = [
                    'month' => $key,
                    'categories' => self::charitiesStackedChartData(self::ENTITY, $status, $category, $year, $value, $timePeriodReferenceService)
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
                new Enum(EnquiryStatisticsTypeEnum::class)
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

        if ($type == EnquiryStatisticsTypeEnum::Enquiries->value) {
            $status = EnquiryStatusEnum::tryFrom($status)?->value;
            $year = in_array($year, EnquiryOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        } elseif ($type == EnquiryStatisticsTypeEnum::Charities->value) {
            $category = in_array($category, CharityCategoryOptions::getRefOptions()->pluck('value')->toArray()) ? $category : null;
            $status = CharityMembershipTypeEnum::tryFrom($status)?->value;
            $year = in_array($year, CharityOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        } elseif ($type == EnquiryStatisticsTypeEnum::Events->value) {
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
            EnquiryStatisticsTypeEnum::Enquiries->value => ClientOptions::only('enquiries', [
                'statuses',
                'years',
                'periods'
            ]),
            EnquiryStatisticsTypeEnum::Events->value => [
                'categories' => EventCategoryOptions::getRefOptions(),
                'statuses' => EventStateEnum::_options(),
                'years' => EventOptions::getYearOptions(),
                'periods' => self::getPeriodOptions()
            ]
        ];

        if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
            $options[EnquiryStatisticsTypeEnum::Charities->value] = [
                'statuses' => CharityMembershipTypeEnum::_options(),
                'categories' => CharityCategoryOptions::getRefOptions(),
                'years' => CharityOptions::getYearOptions(),
                'periods' => self::getPeriodOptions()
            ];
        }

        return [
            'types' => EnquiryStatisticsTypeEnum::_options(),
            'options' => $options
        ];
    }
}
