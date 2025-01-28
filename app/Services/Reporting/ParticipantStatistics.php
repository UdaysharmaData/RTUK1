<?php

namespace App\Services\Reporting;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Services\Charting\StackedAreaChart;
use App\Services\TimePeriodReferenceService;

use App\Modules\Setting\Models\Traits\SiteQueryScopeTrait;

use App\Modules\Setting\Enums\SiteEnum;
use App\Facades\ClientOptions;
use App\Enums\TimeReferenceEnum;
use App\Enums\InvoiceStatusEnum;
use App\Modules\Setting\Enums\OrganisationEnum;
use App\Enums\ParticipantStatusEnum;

use App\Services\ClientOptions\InvoiceOptions;
use App\Services\ClientOptions\EventCategoryOptions;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Enums\ParticipantStatisticsTypeEnum;

use App\Traits\SiteTrait;
use App\Services\Reporting\Traits\OptionsTrait;
use App\Services\Reporting\Traits\EventStatsTrait;
use App\Services\Reporting\Traits\InvoiceStatsTrait;
use App\Services\Reporting\Traits\AnalyticsStatsTrait;
use App\Services\Reporting\Traits\ParticipantStatsTrait;
use App\Services\Reporting\Traits\StructureChartDataTrait;
use App\Services\Reporting\Traits\EventCategoryStatsTrait;

use App\Services\ClientOptions\ParticipantOptions;

class ParticipantStatistics
{
    use SiteQueryScopeTrait, OptionsTrait, StructureChartDataTrait, AnalyticsStatsTrait, ParticipantStatsTrait, EventStatsTrait, EventCategoryStatsTrait, InvoiceStatsTrait, SiteTrait;

    const ENTITY = StatisticsEntityEnum::Participant;

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

        if ($type === ParticipantStatisticsTypeEnum::Participants->value) {
            $data = [
                'stats' => [self::participantsStatsData(self::ENTITY, $year, $status, $category, null, $timePeriodReferenceService)]
            ];
        } elseif ($type === ParticipantStatisticsTypeEnum::Invoices->value) {
            $data = [
                'stats' => [self::invoicesStatsData(self::ENTITY, $year, $status, null, null, $timePeriodReferenceService)]
            ];
        } else {
            $data = [
                'stats' => [
                    self::participantsStatsData(self::ENTITY, $year, $status, $category, null, $timePeriodReferenceService),
                ]
            ];

            if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
                $data['stats'][] = self::invoicesStatsData(self::ENTITY, $year, $status, null, null, $timePeriodReferenceService);
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
        $user = request()->user();
        $userId = $user->isParticipant() ? $user->id : null;

        $months = self::months();

        if ($type == ParticipantStatisticsTypeEnum::Participants->value) {
            foreach ($months as $key => $value) {
                $data[] = [
                    'month' => $key,
                    'categories' => self::participantsStackedAreaChartData(self::ENTITY, $year, $status, $category, $value, $userId, $timePeriodReferenceService)
                ];
            }

            $data = (new StackedAreaChart)->format($data);

        } elseif ($type == ParticipantStatisticsTypeEnum::Invoices->value) {
            foreach ($months as $key => $value) {
                $data[] = [
                    'month' => $key,
                    'categories' => self::invoicesStackedChartData(self::ENTITY, $status, null, $year, $value, $userId, $timePeriodReferenceService)
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
                new Enum(ParticipantStatisticsTypeEnum::class)
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
     * @param mixed $category
     * @param mixed $period
     * @return array
     */
    public static function setParams(mixed $type, mixed $status, mixed $year, mixed $category = null, mixed $period): array
    {
        $period = TimeReferenceEnum::tryFrom($period)?->value;

        if ($type == ParticipantStatisticsTypeEnum::Participants->value) {
            $category = in_array($category, EventCategoryOptions::getRefOptions()->pluck('value')->toArray()) ? $category : null;
            $status = ParticipantStatusEnum::tryFrom($status)?->value;
            $year = in_array($year, ParticipantOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        } elseif ($type == ParticipantStatisticsTypeEnum::Invoices->value) {
            $status = InvoiceStatusEnum::tryFrom($status)?->value;
            $year = in_array($year, InvoiceOptions::getInvoiceItemYearOptions()->pluck('value')->toArray()) ? $year : null;
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
            ParticipantStatisticsTypeEnum::Participants->value => [
                ...ClientOptions::only('participants', [
                    'categories',
                    'statuses',
                    'years',
                    'periods'
                ]),
            ]
        ];

        if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
            $options[ParticipantStatisticsTypeEnum::Invoices->value] = [
                'statuses' => InvoiceStatusEnum::_options(),
                'years' => InvoiceOptions::getInvoiceItemYearOptions(),
                'periods' => self::getPeriodOptions()
            ];
        }

        return [
            'types' => ParticipantStatisticsTypeEnum::_options(),
            'options' => $options
        ];
    }
}
