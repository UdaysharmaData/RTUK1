<?php

namespace App\Traits;

use Laravel\Scout\Searchable;

use App\Enums\EventStateEnum;

trait UseDynamicallySearchableAttributes
{
    use Searchable {
        search as parentSearch;
    }

    use SiteTrait;

    /**
     * Get the indexable data array for the model.
     * This is used by Laravel Scout to build the search index. 
     *
     * @return array
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => ''
        ];
    }

    /**
     * Override the default Laravel scout search method.
     *
     * @param  string $query
     * @param  \Closure  $callback
     * @return \Laravel\Scout\Builder
     */
    public static function search($query = '', $callback = null): \Laravel\Scout\Builder
    {
        return static::parentSearch($query, $callback)->query(function ($builder) {
            $builder->withoutAppends()
                ->withOnly(['image'])
                ->withCount(['events' => function ($q) {
                    $q->state(EventStateEnum::Live);
                }])
                ->where('site_id', static::getSite()->id)
                ->orderBy('name')
                ->orderByDesc('events_count');
        });
    }
}
