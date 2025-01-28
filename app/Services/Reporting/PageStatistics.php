<?php

namespace App\Services\Reporting;

use App\Enums\CombinationEntityEnum;
use App\Enums\TimeReferenceEnum;
use App\Http\Helpers\FormatNumber;
use App\Models\Combination;
use App\Models\Page;
use App\Services\TimePeriodReferenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Modules\Setting\Models\Traits\SiteQueryScopeTrait;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Traits\OptionsTrait;
use App\Services\Reporting\Traits\AnalyticsStatsTrait;

class PageStatistics
{
    use OptionsTrait, AnalyticsStatsTrait;

    /**
     * @param int|null $year
     * @param string|null $period
     * @return array
     */
    public static function summary(?int $year = null, ?string $period = null): array
    {
        if (is_null($value = TimeReferenceEnum::tryFrom($period)?->value) || $period === TimeReferenceEnum::All->value) {
            $timePeriodReferenceService = null;
        } else $timePeriodReferenceService = new TimePeriodReferenceService($period);

        return array_merge([
            'stats' => [
                self::pagesStatsData($year, $timePeriodReferenceService)
            ],
            'analytics' => self::getAnalytics(Page::class, $value, $year)
        ], self::getOptions());
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
        $year = in_array($year, static::getPagesYearOptions()->pluck('value')->toArray()) ? $year : null;
        $period = TimeReferenceEnum::tryFrom($period)?->value;

        return array($year, $period);
    }

    /**
     * @return \array[][]
     */
    public static function getOptions(): array
    {
        return [
            'options' => [
                'years' => self::getPagesYearOptions(),
                'periods' => self::getPeriodOptions()
            ]
        ];
    }

    /**
     * @param int|null $year
     * @param TimePeriodReferenceService|null $period
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function pagesSummaryQuery(?int $year = null, ?TimePeriodReferenceService $period = null): Builder|HigherOrderWhenProxy|null
    {
        return Page::query()
            ->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
            ->when($period, fn($query) => $query->where('created_at', '>=', $period->toCarbonInstance()));
    }

    /**
     * @param int|null $year
     * @param TimePeriodReferenceService|null $period
     * @return Collection|array
     */
    protected static function pagesStatsData(?int $year, ?TimePeriodReferenceService $period = null): \Illuminate\Support\Collection|array
    {
        return [
            'name' => 'Pages',
            'total' => FormatNumber::format(self::pagesSummaryQuery($year, $period)->count()),
            'type_param_value' => 'pages'
        ];
    }

    /**
     * @return mixed
     */
    public static function getPagesYearOptions(): mixed
    {
        return Cache::remember('page_stats_year_filter_options', now()->addMonth(), function () {
            $years = Page::query()
                ->selectRaw('DISTINCT YEAR(created_at) AS year')
                ->whereNotNull('created_at')
                // ->orderByDesc('created_at')
                ->pluck('year')
                ->sortDesc();

            return $years->map(function ($option, $key) {
                return [
                    'label' => (string) $option,
                    'value' => $option
                ];
            })->values();
        });
    }
}
