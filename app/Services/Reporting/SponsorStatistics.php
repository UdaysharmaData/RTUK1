<?php

namespace App\Services\Reporting;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

use App\Enums\TimeReferenceEnum;
use App\Services\TimePeriodReferenceService;
use App\Services\ClientOptions\SponsorOptions;
use App\Services\Reporting\Traits\OptionsTrait;
use App\Services\Reporting\Traits\SponsorStatsTrait;
use App\Services\Reporting\Enums\StatisticsEntityEnum;

class SponsorStatistics
{
    use SponsorStatsTrait, OptionsTrait;

    const ENTITY = StatisticsEntityEnum::Sponsor;

    /**
     * @param  int|null     $year
     * @param  string|null  $period
     * @return array
     */
    public static function generateStatsSummary(?int $year = null, ?string $period = null): array
    {
        if (is_null(TimeReferenceEnum::tryFrom($period)?->value) || $period === TimeReferenceEnum::All->value) {
            $timePeriodReferenceService = null;
        } else $timePeriodReferenceService = new TimePeriodReferenceService($period);

        $data = [
            'stats' => [self::sponsorsStatsData(self::ENTITY, $year, $timePeriodReferenceService)]
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
     * @return array
     */
    public static function setParams(mixed $year, mixed $period): array
    {
        $year = in_array($year, SponsorOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        $period = TimeReferenceEnum::tryFrom($period)?->value;

        return array(
            $year,
            $period,
        );
    }

    /**
     * @return array
     */
    public static function getOptions(): array
    {
        return [
            // 'options' => [
            //     'years' => SponsorOptions::getYearOptions(),
            //     'periods' => self::getPeriodOptions()
            // ]
        ];
    }
}
