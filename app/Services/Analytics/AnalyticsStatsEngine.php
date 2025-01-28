<?php

namespace App\Services\Analytics;

use App\Services\Analytics\Contracts\AnalyzableInterface;
use App\Services\PercentageChange;
use App\Services\TimePeriodReferenceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\TimeReferenceEnum;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\NoReturn;

abstract class AnalyticsStatsEngine
{
    /**
     * @var string
     */
    protected string $statsPeriod = 'All';
    /**
     * @var string
     */
    protected string $cacheDuration = '1h';
    /**
     * @var string
     */
    protected string $analyticsType;
    /**
     * @var string
     */
    protected string $className;

    /**
     * @param AnalyzableInterface $analyzable
     */
    #[NoReturn] public function __construct(protected AnalyzableInterface $analyzable) {
        $this->className = Str::snake(class_basename($this->analyzable));
        $this->statsPeriod = request('stats_period')
            ? TimeReferenceEnum::tryFrom(request('stats_period'))?->value ?? 'All'
            : 'All'
        ;
    }

    /**
     * @param string $timeReference
     * @return Carbon|null
     */
    protected static function getComputedTimeByReference(string $timeReference): ?Carbon
    {
        $dictionary = [
            '1h' => now()->addHour(),
            '6h' => now()->addHours(6),
            '12h' => now()->addHours(12),
            '24h' => now()->addHours(24),
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            '90d' => now()->addDays(90),
            '180d' => now()->addDays(180),
            '1y' => now()->addYear(),
            'All' => null
        ];

        return $dictionary[$timeReference] ?? null;
    }

    /**
     * @param string $timeReference
     * @return Carbon|null
     */
    protected static function getComputedPeriod(string $timeReference): ?Carbon
    {
        return (new TimePeriodReferenceService($timeReference))->toCarbonInstance();
    }

    /**
     * @return array
     */
    public abstract function stats(): array;

    /**
     * @param string $value
     * @return $this
     */
    public function setStatsPeriod(string $value): static
    {
        $this->statsPeriod = $value;

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setCacheDuration(string $value): static
    {
        $this->cacheDuration = $value;

        return $this;
    }

    /**
     * @param string $dataType
     * @return string
     */
    public function getAnalyticsCacheKey(string $dataType): string
    {
        $className = class_basename($this->analyzable);

        return sha1("analytics-$this->analyticsType-$className-{$this->analyzable->id}-$dataType-$this->statsPeriod");
    }

    /**
     * @param string $modelClassName
     * @param string $filterNullColumn
     * @return \Closure
     */
    public function customAnalyticsJoin(string $modelClassName, string $filterNullColumn): \Closure
    {
        return function ($join) use($modelClassName, $filterNullColumn) {
            $join->on("$this->analyticsType.id", '=', 'analytics_metadata.metadata_id')
                ->where('analytics_metadata.metadata_type', '=', $modelClassName)
                ->whereNotNull($filterNullColumn)
//                ->when(
//                    $period = self::getComputedPeriod($this->statsPeriod),
//                    fn($query) => $query->whereDate("$this->analyticsType.created_at", '>=', $period)
//                )
            ;
        };
    }

    /**
     * @param Carbon|null $period
     * @param bool $previous
     * @return mixed
     */
    public function analyticsTimelineCount(?Carbon $period = null, bool $previous = false): mixed
    {
        return $this->analyzable->{$this->analyticsType}()
            ->clone()
            ->when(
                $period,
                function ($query) use($period, $previous) {
                    $originalPeriod = TimeReferenceEnum::tryFrom($this->statsPeriod)?->value;

                    if ($previous && $originalPeriod) {
                        $query->where('created_at', '>=', (new TimePeriodReferenceService($originalPeriod))->toCarbonInstance(true))
                            ->where('created_at', '<', $period);
                    } else {
                        $query->where('created_at', '>=', $period);
                    }
                }
            )
            ->count();
    }

    /**
     * @return float
     */
    public function analyticsCountPercentageChange(): float
    {
        $period = self::getComputedPeriod($this->statsPeriod);
        $currentCount = $this->analyticsTimelineCount($period);
        $previousCount = $this->analyticsTimelineCount($period, true);

        return (new PercentageChange)->calculate($currentCount, $previousCount);
    }

    /**
     * @return \Closure
     */
    public static function notBotFilter(): \Closure
    {
        return function (Builder $query) {
            $query->whereHas('metadata', function (Builder $query) {
                $query->where('is_bot', '!=', 1);
            });
        };
    }

    /**
     * @param string $statsPeriod
     * @return array[]
     */
    protected static function getQueryPeriodFromTimeReference(string $statsPeriod): array
    {
        return match ($statsPeriod) {
            '6h' => [
                'previous' => [
                    'from' => now()->subHours(12),
                    'to' => now()->subHours(6)
                ],
                'current' => [
                    'from' => now()->subHours(6),
                    'to' => now()
                ]
            ],
            '12h' => [
                'previous' => [
                    'from' => now()->subHours(24),
                    'to' => now()->subHours(12)
                ],
                'current' => [
                    'from' => now()->subHours(12),
                    'to' => now()
                ]
            ],
            '24h' => [
                'previous' => [
                    'from' => now()->subHours(48),
                    'to' => now()->subHours(24)
                ],
                'current' => [
                    'from' => now()->subHours(24),
                    'to' => now()
                ]
            ],
            '7d' => [
                'previous' => [
                    'from' => now()->subDays(14),
                    'to' => now()->subDays(7)
                ],
                'current' => [
                    'from' => now()->subDays(7),
                    'to' => now()
                ]
            ],
            '30d' => [
                'previous' => [
                    'from' => now()->subDays(60),
                    'to' => now()->subDays(30)
                ],
                'current' => [
                    'from' => now()->subDays(30),
                    'to' => now()
                ]
            ],
            '90d' => [
                'previous' => [
                    'from' => now()->subDays(180),
                    'to' => now()->subDays(90)
                ],
                'current' => [
                    'from' => now()->subDays(90),
                    'to' => now()
                ]
            ],
            '180d' => [
                'previous' => [
                    'from' => now()->subDays(360),
                    'to' => now()->subDays(180)
                ],
                'current' => [
                    'from' => now()->subDays(180),
                    'to' => now()
                ]
            ],
            '1y' => [
                'previous' => [
                    'from' => now()->subYears(2),
                    'to' => now()->subYear()
                ],
                'current' => [
                    'from' => now()->subYear(),
                    'to' => now()
                ]
            ],
            default => [
                'previous' => [
                    'from' => now()->subHours(2),
                    'to' => now()->subHour()
                ],
                'current' => [
                    'from' => now()->subHour(),
                    'to' => now()
                ]
            ],
        };
    }

    /**
     * @param string $timeReference
     * @param string $class
     * @param \Closure $calculator
     * @return float|int
     */
    protected static function calculatePercentage(string $timeReference, string $class, \Closure $calculator): int|float
    {
        $period = self::getComputedPeriod($timeReference);

        if (is_null($period)) {
            $previousCount = 0;
        } else $previousCount = $calculator($class, $period, true);

        $currentCount = $calculator($class, $period);

        return (new PercentageChange)->calculate($currentCount, $previousCount);
    }

    /**
     * @param mixed $previousCount
     * @param mixed $currentCount
     * @return float|int
     */
    protected static function computeChange(mixed $previousCount, mixed $currentCount): int|float
    {
        if ($previousCount === 0) {
            if ($currentCount > 0) {
                $percent = 100;
            } elseif ($currentCount < 0) {
                $percent = -100;
            } else {
                $percent = 0;
            }
        } else {
            $percent = (($currentCount - $previousCount) / ($previousCount)) * 100;
        }
        return $percent;
    }


    /**
     * @param string|null $duration
     * @return Carbon|null
     */
    protected static function getCarbonInstanceOfCacheDuration(?string $duration = '1h'): Carbon|null
    {
        return self::getComputedTimeByReference($duration);
    }
}
