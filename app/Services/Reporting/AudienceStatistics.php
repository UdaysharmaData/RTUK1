<?php

namespace App\Services\Reporting;

use App\Services\ClientOptions\AudienceOptions;
use App\Services\Reporting\Traits\AudienceStatsTrait;
use Illuminate\Validation\Rule;
use App\Enums\TimeReferenceEnum;
use Illuminate\Support\Facades\Validator;
use App\Services\TimePeriodReferenceService;
use App\Modules\Setting\Models\Traits\SiteQueryScopeTrait;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Traits\OptionsTrait;

class AudienceStatistics
{
    use SiteQueryScopeTrait, OptionsTrait, AudienceStatsTrait;

    const ENTITY = StatisticsEntityEnum::Audience;

    /**
     * @param int|null $year
     * @param string|null $period
     * @return array
     */
    public static function generateStatsSummary(?int $year = null, ?string $period = null): array
    {
        if (is_null(TimeReferenceEnum::tryFrom($period)?->value) || $period === TimeReferenceEnum::All->value) {
            $timePeriodReferenceService = null;
        } else $timePeriodReferenceService = new TimePeriodReferenceService($period);

        $data = [
            'stats' => [self::audiencesStatsData(self::ENTITY, $year, $timePeriodReferenceService)]
        ];

        $data['stats'] = array_merge($data['stats']);

        return array_merge($data, self::getOptions());
    }

    /**
     * @param bool $required
     * @return \Illuminate\Validation\Validator
     */
    public static function getParamsValidator(bool $required = true): \Illuminate\Validation\Validator
    {
        return Validator::make(request()->all(), [
            'period' => [
                'sometimes', Rule::in(TimeReferenceEnum::values())
            ]
        ]);
    }

    /**
     * @param mixed $year
     * @param mixed $period
     * @return array
     */
    public static function setParams(mixed $year, mixed $period): array
    {
        $period = TimeReferenceEnum::tryFrom($period)?->value;
        $year = in_array($year, AudienceOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;

        return array($year, $period);
    }

    /**
     * @return \array[][]
     */
    public static function getOptions(): array
    {
        return [
            // 'options' => [
            //     'years' => AudienceOptions::getYearOptions(),
            //     'periods' => self::getPeriodOptions()
            // ]
        ];
    }
}
