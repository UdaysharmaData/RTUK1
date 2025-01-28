<?php

namespace App\Services\Reporting;

use App\Enums\CombinationEntityEnum;
use App\Enums\TimeReferenceEnum;
use App\Http\Helpers\FormatNumber;
use App\Models\Combination;
use App\Services\TimePeriodReferenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HigherOrderWhenProxy;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Services\Reporting\Enums\StatisticsEntityEnum;
use App\Services\Reporting\Traits\OptionsTrait;
use App\Services\Reporting\Traits\AnalyticsStatsTrait;

class CombinationStatistics
{
    use OptionsTrait, AnalyticsStatsTrait;

    const ENTITY = StatisticsEntityEnum::Combination;

    const RELATIONS_MAP = [
        'categories' => 'eventCategory',
        'regions' => 'region',
        'cities' => 'city',
        'venues' => 'venue'
    ];

    /**
     * @param int|null $year
     * @param string|null $period
     * @param string|null $type
     * @return array
     */
    public static function summary(?int $year = null, ?string $period = null, ?string $type = null): array
    {
        if (is_null($value = TimeReferenceEnum::tryFrom($period)?->value) || $period === TimeReferenceEnum::All->value) {
            $timePeriodReferenceService = null;
        } else $timePeriodReferenceService = new TimePeriodReferenceService($period);

        return array_merge([
            'stats' => self::combinationsStatsData($year, $timePeriodReferenceService, $type),
            'analytics' => self::getAnalytics(Combination::class, $value, $year)
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
            ],
            'type' => [
                'sometimes', Rule::in(CombinationEntityEnum::values())
            ]
        ]);
    }

    /**
     * @param mixed $year
     * @param mixed $period
     * @param mixed $type
     * @return array
     */
    public static function setParams(mixed $year, mixed $period, mixed $type): array
    {
        $year = in_array($year, static::getCombinationsYearOptions()->pluck('value')->toArray()) ? $year : null;
        $period = TimeReferenceEnum::tryFrom($period)?->value;
        $type = CombinationEntityEnum::tryFrom($type)?->value;

        return array($year, $period, $type);
    }

    /**
     * @return \array[][]
     */
    public static function getOptions(): array
    {
        return [
            // 'options' => [
            //     'years' => self::getCombinationsYearOptions(),
            //     'periods' => self::getPeriodOptions(),
            //     'types' => self::getCombinationsTypeOptions()
            // ]
        ];
    }

    /**
     * @param string|null $relation
     * @param int|null $year
     * @param TimePeriodReferenceService|null $period
     * @return Builder|HigherOrderWhenProxy|mixed
     */
    public static function combinationsSummaryQuery(?string $relation = null, ?int $year = null, ?TimePeriodReferenceService $period = null): Builder|HigherOrderWhenProxy|null
    {
        return Combination::query()
            ->when($relation, fn($query) => $query->whereHas($relation))
            ->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
            ->when($period, fn($query) => $query->where('created_at', '>=', $period->toCarbonInstance()));
    }

    /**
     * @param int|null $year
     * @param TimePeriodReferenceService|null $period
     * @param string|null $entity
     * @return Collection|array
     */
    protected static function combinationsStatsData(?int $year, ?TimePeriodReferenceService $period = null, ?string $entity = null): \Illuminate\Support\Collection|array
    {
        $data = [];
        $array = self::RELATIONS_MAP;

        if ($entity) {
            $array = array_filter(
                $array,
                fn($key) => $key === $entity,
            ARRAY_FILTER_USE_KEY
            );
        }

        foreach ($array as $key => $relation) {
            $data[] = [
                'name' => ucfirst($key),
                'total' => FormatNumber::format(
                    self::combinationsSummaryQuery($relation, $year, $period)->count()
                ),
                'type_param_value' => strtolower($key)
            ];
        }

        $data = collect($data);

        return $entity
            ? $data
            : $data->prepend([
                'name' => 'Combinations',
                'total' => FormatNumber::format(self::combinationsSummaryQuery(null, $year, $period)->count()),
                'type_param_value' => 'combinations'
            ]);
    }

    /**
     * @return mixed
     */
    public static function getCombinationsYearOptions(): mixed
    {
        return Cache::remember('combination_stats_year_filter_options', now()->addMonth(), function () {
            $years = Combination::query()
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

    /**
     * @return mixed
     */
    public static function getCombinationsTypeOptions(): mixed
    {
        return Cache::remember('combination_stats_type_filter_options', now()->addMonth(), function () {
            return CombinationEntityEnum::_options();
        });
    }
}
