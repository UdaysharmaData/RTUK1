<?php

namespace App\Services\GlobalSearchService;

use Illuminate\Support\Collection;

use App\Models\City;
use App\Models\Page;
use App\Models\Venue;
use App\Models\Region;
use App\Models\Combination;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventCategory;
use App\Services\GlobalSearchService\ModelSearchAspect;
use App\Services\GlobalSearchService\Concerns\HandleSearchHistory;

class GlobalSearch
{
    use HandleSearchHistory;

    protected $aspects = [];

    protected $limit = 10;

    const SEARCHABLE_MODELS = [
        Event::class,
        EventCategory::class,
        Region::class,
        City::class,
        Venue::class,
        Combination::class,
        Charity::class,
        Page::class,
    ];

    public function __construct() 
    {
        $this->limit = request('per_page', 10);
    }

    /**
     * Register a model search aspect
     *
     * @param  ModelSearchAspect $searchAspect
     * @return mixed
     */
    public function registerModelSearchAspect(ModelSearchAspect $searchAspect)
    {
        $this->aspects[$searchAspect->getType()] = $searchAspect;

        return $this;
    }

    /**
     * Register a model to be searchable
     *
     * @param  string $modelClass
     * @param  \Closure $searchCondition
     * @return mixed
     */
    public function registerModel(string $modelClass, \Closure $searchCondition = null)
    {
        if ($searchCondition) {
            $searchAspect = new ModelSearchAspect($modelClass, $searchCondition);
        } else {
            $searchAspect = new ModelSearchAspect($modelClass);
        }

        $this->aspects[$searchAspect->getType()] = $searchAspect;

        return $this;
    }

    /**
     * register all the searchable models
     *
     * @return mixed
     */
    public function registerAllSearchableModels()
    {
        foreach (static::SEARCHABLE_MODELS as $model) {
            $this->registerModel($model);
        }

        $this->limit(request('per_page', 2));

        return $this;
    }

    /**
     * set the limit of the search results
     *
     * @param  int $limit
     * @return void
     */
    public function limit(int $limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * search for the term 
     *
     * @param  mixed $term
     * @return Collection
     */
    public function search(string $term): Collection
    {
        $results = collect();

        foreach ($this->aspects as $aspect) {
            $results[$aspect->getType()] = $aspect->getPaginatedResults($term, $this->limit);
        }
        
        return $results;
    }
    
    /**
     * get the searchable models
     *
     * @return Collection
     */
    public function getSearchableModels(): Collection
    {
        return collect($this->aspects)->map(function ($aspect) {
            return $aspect->getModel();
        });
    }
}
