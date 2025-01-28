<?php

namespace App\Services\Analytics;

use App\Services\PercentageChange;
use App\Services\TimePeriodReferenceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use App\Enums\TimeReferenceEnum;
use App\Models\Interaction;

class AnalyticsInteractionsStats extends AnalyticsStatsEngine
{
    /**
     * @var string
     */
    protected string $analyticsType = 'interactions';

    /**
     * @return mixed
     */
    private function totalInteractions(): mixed
    {
        $key = $this->getAnalyticsCacheKey('total-interactions');

        return Cache::remember($key, $this->getComputedTimeByReference($this->cacheDuration), function () {
            return $this->analyzable->interactions()
                ->when(
                    $period = self::getComputedPeriod($this->statsPeriod),
                    fn($query) => $query->where('created_at', '>=', $period)
                )
                ->count();
        });
    }

    /**
     * @return Collection
     */
    public function topInteractionsByCountries(): Collection
    {
        $key = $this->getAnalyticsCacheKey('top-countries');

        return Cache::remember($key, $this->getComputedTimeByReference($this->cacheDuration), function () {
            return $this->analyzable->interactions()
                ->selectRaw('country, COUNT(country) as countries_count')
                ->join('analytics_metadata', $this->customAnalyticsJoin(
                    Interaction::class,
                    'country')
                )
                ->orderBy('countries_count', 'DESC')
                ->limit(6)
                ->groupBy('country')
                ->get();
        });
    }

    /**
     * @return Collection
     */
    public function topInteractionsByDevices(): Collection
    {
        $key = $this->getAnalyticsCacheKey('top-devices');

        return Cache::remember($key, $this->getComputedTimeByReference($this->cacheDuration), function () {
            return $this->analyzable->interactions()
                ->selectRaw('device_type, COUNT(device_type) as interactions_count')
                ->join('analytics_metadata', $this->customAnalyticsJoin(
                    Interaction::class,
                    'device_type')
                )
                ->orderBy('interactions_count', 'DESC')
                ->limit(3)
                ->groupBy('device_type')
                ->get();
        });
    }

    /**
     * @return Collection
     */
    public function interactionsByType(): Collection
    {
        $key = $this->getAnalyticsCacheKey('interaction-types');

        return Cache::remember($key, $this->getComputedTimeByReference($this->cacheDuration), function () {
            return $this->analyzable->interactions()
                ->selectRaw('type, COUNT(type) as interaction_type_count')
                ->when(
                    $period = self::getComputedPeriod($this->statsPeriod),
                    fn($query) => $query->whereDate('interactions.created_at', '>=', $period)
                )
                ->orderBy('interaction_type_count', 'DESC')
                ->groupBy('type')
                ->get();
        });
    }

    /**
     * @return float|int
     */
    public function interactionsCountPercent(): float|int
    {
        $key = $this->getAnalyticsCacheKey('count-percent');

        return Cache::remember($key, $this->getComputedTimeByReference($this->cacheDuration), function () {
            return $this->analyticsCountPercentageChange();
        });
    }

    /**
     * @param string $class
     * @param string $timeReference
     * @param string|null $year
     * @return mixed
     */
    public static function combinedInteractionsCount(string $class, string $timeReference = 'All', ?string $year = null): mixed
    {
        $key = sha1('total_interactions_count_'. $class . $timeReference . $year);
        return Cache::remember($key, static::getCarbonInstanceOfCacheDuration(), function () use ($year, $timeReference, $class) {
            return Interaction::query()
                ->whereHasMorph('interactable', [$class])
                ->when(
                    $period = self::getComputedPeriod($timeReference),
                    fn($query) => $query->where("$this->analyticsType.created_at", '>=', $period)
                )
                ->when($year, fn($query) => $query->whereYear("$this->analyticsType.created_at", '=', $year))
                ->count();
        });
    }

    /**
     * @param string $class
     * @param string $timeReference
     * @param string|null $year
     * @return mixed
     */
    public static function combinedInteractionsCountPercentChange(string $class, string $timeReference = 'All', ?string $year = null): mixed
    {
        $percent = self::calculatePercentage($timeReference, $class, self::getTimelineCount());
        $key = sha1('total_interactions_count_percent_change_'. $class . $timeReference . $year);

        return Cache::remember($key, static::getCarbonInstanceOfCacheDuration(), function () use ($percent) {
            return $percent;
        });
    }

    /**
     * @return \Closure
     */
    private static function getTimelineCount(): \Closure
    {
        return function (string $class, ?Carbon $period, bool $previous = false, ?string $year = null) {
            return Interaction::query()
                ->clone()
                ->whereHasMorph('interactable', [$class])
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
                ->when(
                    $year,
                    function ($query) use($year, $previous) {
                        $previousYear = (string)($year - 1);

                        if ($previous && $year) {
                            $query->whereYear('created_at', '=', $previousYear);
                        } else {
                            $query->whereYear('created_at', '=', $year);
                        }
                    }
                )
                ->count();
        };
    }

    /**
     * @return array
     */
    public function stats(): array
    {
        $previousCount = $this->analyzable->interactions_previous_count ?? 0;
        $currentCount = $this->analyzable->interactions_current_count ?? 0;

        return [
//            'combined_interactions_count' => self::combinedInteractionsCount(Page::class),
//            'combined_interactions_count_change' => self::combinedInteractionsCountPercentChange(Page::class),

//            'interactions_count' => $this->totalInteractions(),
//            'top_countries_interactions' => $this->topInteractionsByCountries(),
//            'top_devices_interactions' => $this->topInteractionsByDevices(),
//            'interaction_types' => $this->interactionsByType(),
//            'interaction_percent_change' => $this->interactionsCountPercent(),

            'interactions_count' => $this->analyzable->interactions_count ?? 0,
            'interaction_percent_change' => (new PercentageChange)->calculate($currentCount, $previousCount),
            'top_countries_interactions' => $this->analyzable->withExtras ? $this->topInteractionsByCountries() : [],
            'top_devices_interactions' => $this->analyzable->withExtras ? $this->topInteractionsByDevices() : [],
            'interaction_types' => [],

//            'interactions_count' => 0,
//            'top_countries_interactions' => [],
//            'top_devices_interactions' => [],
//            'interaction_types' => [],
//            'interaction_percent_change' => 0
        ];
    }
}
