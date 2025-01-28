<?php

namespace App\Services\Reporting;

use App\Enums\TimeReferenceEnum;
use App\Services\Charting\StackedAreaChart;
use App\Services\Charting\StackedColumnChart;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EventDataService;
use App\Services\DataServices\InvoiceDataService;
use App\Services\DataServices\ParticipantDataService;
use App\Services\TimePeriodReferenceService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Validator;

use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\RoleNameEnum;
use App\Enums\EventStateEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Modules\Setting\Enums\OrganisationEnum;
use App\Enums\ParticipantStatusEnum;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Enums\DashboardStatisticsTypeEnum;

use App\Traits\SiteTrait;
use App\Services\Reporting\Traits\OptionsTrait;
use App\Services\Reporting\Traits\StructureChartDataTrait;
use App\Services\Reporting\Traits\PercentageChangeTrait;

use App\Services\Reporting\Traits\EventStatsTrait;
use App\Services\Reporting\Traits\EntryStatsTrait;
use App\Services\Reporting\Traits\InvoiceStatsTrait;
use App\Services\Reporting\Traits\ParticipantStatsTrait;
use App\Services\Reporting\Traits\EventCategoryStatsTrait;
use App\Modules\Setting\Models\Traits\SiteQueryScopeTrait;
use App\Services\ClientOptions\EventCategoryOptions;
use App\Services\ClientOptions\EventOptions;
use App\Services\ClientOptions\InvoiceOptions;
use App\Services\ClientOptions\ParticipantOptions;

class DashboardStatistics
{
    use SiteQueryScopeTrait, PercentageChangeTrait, OptionsTrait, StructureChartDataTrait, ParticipantStatsTrait, InvoiceStatsTrait, EventStatsTrait, EntryStatsTrait, EventCategoryStatsTrait, SiteTrait;

    const ENTITY = StatisticsEntityEnum::Dashboard;

    /**
     * @param string|null $type
     * @param int|null $year
     * @param string|null $status
     * @param string|null $category
     * @param string|null $period
     * @return array
     * @throws \Exception
     */
    public static function generateStatsSummary(?string $type = null, ?int $year = null, ?string $status = null, ?string $category = null, ?string $period = null): array
    {
        if (is_null(TimeReferenceEnum::tryFrom($period)?->value) || $period === TimeReferenceEnum::All->value) {
            $timePeriodReferenceService = null;
        } else $timePeriodReferenceService = new TimePeriodReferenceService($period);

        $role = request()->user()->activeRoleName();
        $userId = $role == RoleNameEnum::Participant->value ? request()->user()?->id : null;

        if ($role == RoleNameEnum::Administrator->value) {
            if ($type === DashboardStatisticsTypeEnum::Participants->value) {
                $data = [
                    'stats' => [
                        (new CacheDataManager(
                            (new ParticipantDataService()),
                            'participantsStatsData',
                            [self::ENTITY, $year, $status, $category, null, $timePeriodReferenceService],
                            false,
                            true,
                            null,
                            null,
                            true,
                            ['type' => DashboardStatisticsTypeEnum::Participants->value]
                        ))->getData()
                    ]
                ];
            } elseif ($type === DashboardStatisticsTypeEnum::Events->value) {
                $data = [
                    'stats' => [
                        (new CacheDataManager(
                            (new EventDataService()),
                            'eventsStatsData',
                            [self::ENTITY, $status, $category, $year, $timePeriodReferenceService],
                            false,
                            true,
                            null,
                            null,
                            true,
                            ['type' => DashboardStatisticsTypeEnum::Events->value]
                        ))->getData(),
                    ]
                ];
            } elseif ($type === DashboardStatisticsTypeEnum::Invoices->value) {
                $data = [
                    'stats' => [
                        (new CacheDataManager(
                            (new InvoiceDataService()),
                            'invoicesStatsData',
                            [self::ENTITY, $year, $status, null, $userId, $timePeriodReferenceService],
                            false,
                            true,
                            null,
                            null,
                            true,
                            ['type' => DashboardStatisticsTypeEnum::Invoices->value]
                        ))->getData()
                    ]
                ];
            } else {
                $data = [
                    'stats' => [
                        (new CacheDataManager(
                            (new ParticipantDataService()),
                            'participantsStatsData',
                            [self::ENTITY, $year, $status, $category, null, $timePeriodReferenceService],
                            false,
                            true,
                            null,
                            null,
                            true,
                            ['type' => DashboardStatisticsTypeEnum::Participants->value]
                        ))->getData(),
                        (new CacheDataManager(
                            (new EventDataService()),
                            'eventsStatsData',
                            [self::ENTITY, $status, $category, $year, $timePeriodReferenceService],
                            false,
                            true,
                            null,
                            null,
                            true,
                            ['type' => DashboardStatisticsTypeEnum::Events->value]
                        ))->getData()
                    ]
                ];

                //if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
                    $data['stats'][] = (new CacheDataManager(
                        (new InvoiceDataService()),
                        'invoicesStatsData',
                        [self::ENTITY, $year, $status, $category, $userId, $timePeriodReferenceService],
                        false,
                        true,
                        null,
                        null,
                        true,
                        ['type' => DashboardStatisticsTypeEnum::Invoices->value]
                    ))->getData();
                //}
            }
        } elseif($role == RoleNameEnum::Participant->value) {
            if ($type === DashboardStatisticsTypeEnum::Entries->value) {
                $data = [
                    'stats' => [
                        (new CacheDataManager(
                            (new ParticipantDataService()),
                            'entriesStatsData',
                            [self::ENTITY, $year, $status, $category, null, $userId, $timePeriodReferenceService],
                            false,
                            true,
                            null,
                            null,
                            true,
                            ['type' => DashboardStatisticsTypeEnum::Entries->value]
                        ))->getData()
                    ]
                ];
            } elseif ($type === DashboardStatisticsTypeEnum::Invoices->value) {
                $data = [
                    'stats' => [
                        (new CacheDataManager(
                            (new InvoiceDataService()),
                            'invoicesStatsData',
                            [self::ENTITY, $year, $status, $category, $userId, $timePeriodReferenceService],
                            false,
                            true,
                            null,
                            null,
                            true,
                            ['type' => DashboardStatisticsTypeEnum::Invoices->value]
                        ))->getData()
                    ]
                ];
            } else {
                $data = [
                    'stats' => [
                        (new CacheDataManager(
                            (new ParticipantDataService()),
                            'entriesStatsData',
                            [self::ENTITY, $year, $status, $category, null, $userId, $timePeriodReferenceService],
                            false,
                            true,
                            null,
                            null,
                            true,
                            ['type' => DashboardStatisticsTypeEnum::Entries->value]
                        ))->getData()
                    ]
                ];

                if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
                    $data['stats'][] = (new CacheDataManager(
                        (new InvoiceDataService()),
                        'invoicesStatsData',
                        [self::ENTITY, $year, $status, $category, $userId, $timePeriodReferenceService],
                        false,
                        true,
                        null,
                        null,
                        true,
                        ['type' => DashboardStatisticsTypeEnum::Invoices->value]
                    ))->getData();
                }
            }
        } else $data = [];

        return array_merge($data, self::getOptions()[$role] ?? []);
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
        $site = clientSiteId();
        $user = request()->user();
        $userId = $user->isParticipant() ? $user->id : null;
        $cacheKey = "generate_{$site}_{$type}_stats_graph_{$year}_{$status}_{$category}_{$period}_user_$userId";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($timePeriodReferenceService, $userId, $user, $data, $type, $year, $status, $category) {
            $months = self::months();

            if ($user->isAdmin()) {
                if ($type == DashboardStatisticsTypeEnum::Participants->value) {
                    foreach ($months as $key => $value) {
                        $data[] = [
                            'month' => $key,
                            'categories' => self::participantsStackedAreaChartData(self::ENTITY, $year, $status, $category, $value, $userId, $timePeriodReferenceService)
                        ];
                    }

                    $data = (new StackedColumnChart)->format($data);

                } elseif ($type == DashboardStatisticsTypeEnum::Events->value) {
                    foreach ($months as $key => $value) {
                        $data[] = [
                            'month' => $key,
                            'categories' => self::eventsStackedChartData(self::ENTITY, $status, $category, $year, $value, $timePeriodReferenceService)
                        ];
                    }

                    $data = (new StackedColumnChart)->format($data);

                } elseif ($type == DashboardStatisticsTypeEnum::Invoices->value) {
                    foreach ($months as $key => $value) {
                        $data[] = [
                            'month' => $key,
                            'categories' => self::invoicesStackedChartData(self::ENTITY, $status, $category, $year, $value, $userId, $timePeriodReferenceService)
                        ];
                    }

                    $data = (new StackedAreaChart)->format($data);
                }
            } elseif ($user->isParticipant()) {
                if ($type == DashboardStatisticsTypeEnum::Entries->value) {
                    foreach ($months as $key => $value) {
                        $data[] = [
                            'month' => $key,
                            'categories' => self::participantsStackedAreaChartData(self::ENTITY, $year, $status, $category, $value, $userId, $timePeriodReferenceService)
                        ];
                    }

                    $data = (new StackedColumnChart)->format($data);

                } elseif ($type == DashboardStatisticsTypeEnum::Invoices->value) {
                    foreach ($months as $key => $value) {
                        $data[] = [
                            'month' => $key,
                            'categories' => self::invoicesStackedChartData(self::ENTITY, $status, $category, $year, $value, $userId, $timePeriodReferenceService)
                        ];
                    }

                    $data = (new StackedAreaChart)->format($data);
                }
            }

            return $data;
        });
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
                new Enum(DashboardStatisticsTypeEnum::class)
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

        if ($type == DashboardStatisticsTypeEnum::Participants->value) {
            $category = in_array($category, EventCategoryOptions::getRefOptions()->pluck('value')->toArray()) ? $category : null;
            $status = ParticipantStatusEnum::tryFrom($status)?->value;
            $year = in_array($year, ParticipantOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        } elseif ($type == DashboardStatisticsTypeEnum::Events->value) {
            $category = in_array($category, EventCategoryOptions::getRefOptions()->pluck('value')->toArray()) ? $category : null;
            $status = EventStateEnum::tryFrom($status)?->value;
            $year = in_array($year, EventOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        } elseif ($type == DashboardStatisticsTypeEnum::Invoices->value) {
            $status = InvoiceStatusEnum::tryFrom($status)?->value;
            $year = in_array($year, InvoiceOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        } elseif ($type == DashboardStatisticsTypeEnum::Entries->value) {
            $category = in_array($category, EventCategoryOptions::getRefOptions('entries')->pluck('value')->toArray()) ? $category : null;
            $year = in_array($year, ParticipantOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
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
        $administratorOptions = [
            'participants' => [
                'categories' => EventCategoryOptions::getRefOptions(),
                'statuses' => ParticipantStatusEnum::_options(),
                'years' => ParticipantOptions::getYearOptions(),
                'periods' => self::getPeriodOptions()
            ],
            'events' => [
                'categories' => EventCategoryOptions::getRefOptions(),
                'statuses' => EventStateEnum::_options(),
                'years' => EventOptions::getYearOptions(),
                'periods' => self::getPeriodOptions()
            ]
        ];

        $participantOptions = [
            'entries' => [
                'categories' => EventCategoryOptions::getRefOptions('entries'),
                'statuses' => ParticipantStatusEnum::_options(),
                'years' => ParticipantOptions::getYearOptions(),
                'periods' => self::getPeriodOptions()
            ]
        ];

        //if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
            $administratorOptions['invoices'] = [
                'statuses' => InvoiceStatusEnum::_options(),
                'years' => InvoiceOptions::getYearOptions(),
                'periods' => self::getPeriodOptions()
            ];
        //}

        if (SiteEnum::belongsToOrganisation(OrganisationEnum::SportsMediaAgency)) {
            $participantOptions['invoices'] = [
                'statuses' => InvoiceStatusEnum::_options(),
                'years' => InvoiceOptions::getYearOptions(),
                'periods' => self::getPeriodOptions()
            ];
        }

        return [
            'administrator' => [
                'types' => DashboardStatisticsTypeEnum::_options(),
                'options' => $administratorOptions
            ],
            'participant' => [
                'types' => DashboardStatisticsTypeEnum::_options(),
                'options' => $participantOptions
            ]
        ];
    }

}
