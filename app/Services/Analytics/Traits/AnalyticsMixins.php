<?php

namespace App\Services\Analytics\Traits;

use App\Traits\UseDynamicallyAppendedAttributes;
use Carbon\Carbon;

trait AnalyticsMixins
{
    use UseDynamicallyAppendedAttributes;

    /**
     * @param string $analyticsType
     * @param string $dataType
     * @return string
     */
    public function getAnalyticsCacheKey(string $analyticsType, string $dataType): string
    {
        $className = class_basename($this);
        return sha1("analytics-$analyticsType-$className-$this->id-$dataType");
    }

    /**
     * @param string $analyticsType
     * @param string $modelClassName
     * @param string $filterNullColumn
     * @return \Closure
     */
    public static function customAnalyticsJoin(string $analyticsType, string $modelClassName, string $filterNullColumn): \Closure
    {
        return function ($join) use($analyticsType, $modelClassName, $filterNullColumn) {
            $join->on("$analyticsType.id", '=', 'analytics_metadata.metadata_id')
                ->where('analytics_metadata.metadata_type', '=', $modelClassName)
                ->whereNotNull($filterNullColumn);
        };
    }

    /**
     * @param string $analyticsType
     * @param array<int, Carbon> $timeline
     * @return mixed
     */
    public function analyticsTimelineCount(string $analyticsType, array $timeline): mixed
    {
        $from = $timeline[0];
        $to = $timeline[1];

        return $this->{$analyticsType}()
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    /**
     * @param string $analyticsType
     * @return float|int
     */
    public function analyticsHourlyCountPercentage(string $analyticsType): float|int
    {
        $lastHourCount = $this->analyticsTimelineCount($analyticsType, [now()->subHours(2), now()->subHour()]);
        $thisHourCount = $this->analyticsTimelineCount($analyticsType, [now()->subHour(), now()]);
        $totalCount = $this->totalCount->total;

        if ($totalCount === 0) return 0;

        return round(
            ((($thisHourCount - $lastHourCount) / ($totalCount)) * 100),
            1
        );
    }
}
