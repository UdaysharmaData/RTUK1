<?php

namespace App\Traits;

use App\Models\View;
use App\Services\Analytics\AnalyticsViewsStats;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\JoinClause;

trait HasManyViews
{
    /**
     * @var bool
     */
    protected bool $withExtras = false;

    /**
     * @var string[]
     */
    protected array $viewAttributes = [
        'view_stats'
    ];

    /**
     * @return MorphMany
     */
    public function views(): MorphMany
    {
        return $this->morphMany(View::class, 'viewable');
    }

    /**
     * @return Attribute
     */
    public function viewStats(): Attribute
    {
        return Attribute::make(
            get: fn () => (new AnalyticsViewsStats($this))->stats(),
        );
    }

    /**
     * @return Collection
     */
    public function topViewsByCountries(): Collection
    {
        return $this->views()
            ->selectRaw('country, COUNT(country) as countries_count')
            ->join('analytics_metadata', function (JoinClause $join) {
                $join->on("views.id", '=', 'analytics_metadata.metadata_id')
                    ->where('analytics_metadata.metadata_type', '=', View::class)
                    ->whereNotNull('country');
            })
            ->orderBy('countries_count', 'DESC')
            ->limit(6)
            ->groupBy('country')
            ->get();
    }

    /**
     * @return Collection
     */
    public function topViewsByDevices(): Collection
    {
        return $this->views()
            ->selectRaw('device_type, COUNT(device_type) as total_views')
            ->join('analytics_metadata', function (JoinClause $join) {
                $join->on("views.id", '=', 'analytics_metadata.metadata_id')
                    ->where('analytics_metadata.metadata_type', '=', View::class)
                    ->whereNotNull('device_type');
            })
            ->orderBy('total_views', 'DESC')
            ->limit(3)
            ->groupBy('device_type')
            ->get();
    }
}
