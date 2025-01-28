<?php

namespace App\Services\Reporting;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

use App\Enums\MedalTypeEnum;
use App\Enums\TimeReferenceEnum;
use App\Services\ClientOptions\MedalOptions;
use App\Services\TimePeriodReferenceService;
use App\Services\Reporting\Traits\OptionsTrait;
use App\Services\Reporting\Traits\MedalStatsTrait;
use App\Services\Reporting\Enums\StatisticsEntityEnum;

class MedalStatistics
{
    use MedalStatsTrait, OptionsTrait;

    const ENTITY = StatisticsEntityEnum::Medal;

    public static function generateStatsSummary(?int $year = null, ?string $period = null, ?string $_type = null)
    {
        if (is_null(TimeReferenceEnum::tryFrom($period)?->value) || $period === TimeReferenceEnum::All->value) {
            $timePeriodReferenceService = null;
        } else $timePeriodReferenceService = new TimePeriodReferenceService($period);

        $data = [
            'stats' => [self::medalsStatsData(self::ENTITY, $year, $timePeriodReferenceService, $_type)]
        ];

        return array_merge($data, self::getOptions());
    }

    /**
     *
     * @param  bool $required
     * @return Illuminate\Validation\Validator
     */
    public static function getParamsValidator(bool $required = true): \Illuminate\Validation\Validator
    {
        return Validator::make(request()->all(), [
            'period' => [
                'sometimes', Rule::in(TimeReferenceEnum::values())
            ],
        ]);
    }

    /**
     * @param  mixed $year
     * @param  mixed $period
     * @param  mixed $_type
     * @return array
     */
    public static function setParams(mixed $year, mixed $period, mixed $_type): array
    {
        $year = in_array($year, MedalOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        $period = TimeReferenceEnum::tryFrom($period)?->value;
        $_type = MedalTypeEnum::tryFrom($_type)?->value;

        return array(
            $year,
            $period,
            $_type
        );
    }

    /**
     * @return array
     */
    public static function getOptions(): array
    {
        return [
            // 'options' => [
            //     'years' => MedalOptions::getYearOptions(),
            //     '_types' => MedalOptions::getMedalTypeOptions(),
            //     'periods' => self::getPeriodOptions()
            // ]
        ];
    }
}
