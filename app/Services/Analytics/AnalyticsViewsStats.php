<?php

namespace App\Services\Analytics;

use App\Services\PercentageChange;
use App\Services\TimePeriodReferenceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use App\Enums\TimeReferenceEnum;
use App\Models\View;

class AnalyticsViewsStats extends AnalyticsStatsEngine
{
    /**
     * @var TimePeriodReferenceService|null
     */
    private ?TimePeriodReferenceService $timePeriodReferenceService = null;

    /**
     * @var string
     */
    protected string $analyticsType = 'views';

    /**
     * @return mixed
     */
    private function totalViews(): mixed
    {
        $key = $this->getAnalyticsCacheKey('total-views');

        return Cache::remember($key, $this->getComputedTimeByReference($this->cacheDuration), function () {
            return $this->analyzable->views()
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
    public function topViewsByCountries(): Collection
    {
        $key = $this->getAnalyticsCacheKey('top-countries');

        return Cache::remember($key, $this->getComputedTimeByReference($this->cacheDuration), function () {
            return $this->analyzable->views()
                ->selectRaw('country, COUNT(country) as countries_count')
                ->join('analytics_metadata', $this->customAnalyticsJoin(
                    View::class,
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
    public function topViewsByDevices(): Collection
    {
        $key = $this->getAnalyticsCacheKey('top-devices');

        return Cache::remember($key, $this->getComputedTimeByReference($this->cacheDuration), function () {
            return $this->analyzable->views()
                ->selectRaw('device_type, COUNT(device_type) as total_views')
                ->join('analytics_metadata', $this->customAnalyticsJoin(
                    View::class,
                    'device_type')
                )
                ->orderBy('total_views', 'DESC')
                ->limit(3)
                ->groupBy('device_type')
                ->get();
        });
    }

    /**
     * @return float|int
     */
    public function viewsCountPercent(): float|int
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
    public static function combinedViewsCount(string $class, string $timeReference = 'All', ?string $year = null): mixed
    {
        $key = sha1('total_views_count_'. $class . $timeReference . $year);

        return Cache::remember($key, static::getCarbonInstanceOfCacheDuration(), function () use ($year, $timeReference, $class) {
            return View::query()
                ->whereHasMorph('viewable', [$class])
                ->when(
                    $period = self::getComputedPeriod($timeReference),
                    fn($query) => $query->where('created_at', '>=', $period)
                )
                ->when($year, fn($query) => $query->whereYear('created_at', '=', $year))
                ->count();
        });
    }

    /**
     * @param string $class
     * @param string $timeReference
     * @param string|null $year
     * @return mixed
     */
    public static function combinedViewsCountPercentChange(string $class, string $timeReference = 'All', ?string $year = null): mixed
    {
        $percent = self::calculatePercentage($timeReference, $class, self::getTimelineCount());
        $key = sha1('total_views_count_percent_change_'. $class . $timeReference . $year);

        return Cache::remember($key, static::getCarbonInstanceOfCacheDuration(), function () use ($percent) {
            return $percent;
        });
    }

    /**
     * @return \Closure
     */
    protected static function getTimelineCount(): \Closure
    {
        return function (string $class, ?Carbon $period, bool $previous = false, ?string $year = null) {
            return View::query()
                ->whereHasMorph('viewable', [$class])
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
        $previousCount = $this->analyzable->views_previous_count ?? 0;
        $currentCount = $this->analyzable->views_current_count ?? 0;

        return [
//            'combined_views_count' => self::combinedViewsCount(Page::class),
//            'combined_views_count_change' => self::combinedViewsCountPercentChange(Page::class),

//            'views_count' => $this->totalViews(),
//            'top_countries_views' => $this->topViewsByCountries(),
//            'top_devices_views' => $this->topViewsByDevices(),
//            'views_percent_change' => $this->viewsCountPercent(),

            'views_count' => $this->analyzable->views_count ?? 0,
            'views_percent_change' => (new PercentageChange)->calculate($currentCount, $previousCount),
            'top_countries_views' => $this->analyzable->withExtras ? $this->topViewsByCountries() : [],
            'top_devices_views' => $this->analyzable->withExtras ? $this->topViewsByDevices() : [],

//            'views_count' => 0,
//            'top_countries_views' => [],
//            'top_devices_views' => [],
//            'views_percent_change' => 0
        ];
    }
}
