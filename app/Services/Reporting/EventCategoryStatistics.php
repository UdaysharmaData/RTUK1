<?php

namespace App\Services\Reporting;

use App\Facades\ClientOptions;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Services\TimePeriodReferenceService;

use App\Enums\TimeReferenceEnum;
use App\Enums\EventCategoryVisibilityEnum;
use App\Services\Reporting\Enums\StatisticsEntityEnum;

use App\Modules\Setting\Models\Traits\SiteQueryScopeTrait;

use App\Modules\Event\Models\EventCategory;
use App\Services\Reporting\Traits\OptionsTrait;
use App\Services\Reporting\Traits\AnalyticsStatsTrait;
use App\Services\Reporting\Traits\EventCategoryStatsTrait;
use App\Services\ClientOptions\EventCategoryOptions;

class EventCategoryStatistics
{
    use SiteQueryScopeTrait, OptionsTrait, AnalyticsStatsTrait, EventCategoryStatsTrait;

    const ENTITY = StatisticsEntityEnum::EventCategory;

    /**
     * @param  int|null     $year
     * @param  string|null  $status
     * @param  string|null  $period
     * @return array
     */
    public static function generateStatsSummary(?int $year = null, ?string $status = null, ?string $period = null): array
    {
        if (is_null(TimeReferenceEnum::tryFrom($period)?->value) || $period === TimeReferenceEnum::All->value) {
            $timePeriodReferenceService = null;
        } else $timePeriodReferenceService = new TimePeriodReferenceService($period);

        $data = [
            'stats' => [self::eventCategoriesStatsData(self::ENTITY, $year, $status, $timePeriodReferenceService)]
        ];

        $data['stats'] = array_merge($data['stats'], self::getAnalytics(EventCategory::class));

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
     * @param  mixed  $year
     * @param  mixed  $status
     * @param  mixed  $period
     * @return array
     */
    public static function setParams(mixed $year, mixed $status, mixed $period): array
    {
        $year = in_array($year, EventCategoryOptions::getYearOptions()->pluck('value')->toArray()) ? $year : null;
        $status = EventCategoryVisibilityEnum::tryFrom($status)?->value;
        $period = TimeReferenceEnum::tryFrom($period)?->value;

        return array($year, $status, $period);
    }

    /**
     * @return \array[][]
     */
    public static function getOptions(): array
    {
        return [
            'options' => [
                // ...ClientOptions::only('event_categories', [
                //     'years',
                //     'periods',
                //     'statuses'
                // ])
            ]
        ];
    }
}
