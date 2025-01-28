<?php

namespace App\Traits;

use App\Models\Interaction;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Services\Analytics\AnalyticsInteractionsStats;
use Illuminate\Database\Query\JoinClause;

trait HasManyInteractions
{
    /**
     * @var bool
     */
    protected bool $withExtras = false;

    /**
     * @var string[]
     */
    protected array $interactionAttributes = [
        'interaction_stats'
    ];

    /**
     * @return MorphMany
     */
    public function interactions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'interactable');
    }

    /**
     * @return Attribute
     */
    public function interactionStats(): Attribute
    {
        return Attribute::make(
            get: fn () => (new AnalyticsInteractionsStats($this))->stats(),
        );
    }

    /**
     * @return Collection
     */
    public function topInteractionsByCountries(): Collection
    {
        return $this->interactions()
            ->selectRaw('country, COUNT(country) as countries_count')
            ->join('analytics_metadata', function (JoinClause $join) {
                $join->on('interactions.id', '=', 'analytics_metadata.metadata_id')
                    ->where('analytics_metadata.metadata_type', '=', Interaction::class)
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
    public function topInteractionsByDevices(): Collection
    {
        return $this->interactions()
            ->selectRaw('device_type, COUNT(device_type) as total_views')
            ->join('analytics_metadata', function (JoinClause $join) {
                $join->on('interactions.id', '=', 'analytics_metadata.metadata_id')
                    ->where('analytics_metadata.metadata_type', '=', Interaction::class)
                    ->whereNotNull('device_type');
            })
            ->orderBy('total_views', 'DESC')
            ->limit(3)
            ->groupBy('device_type')
            ->get();
    }
}
