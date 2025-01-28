<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

use App\Models\SearchHistory;

trait HasManySearchHistories
{
    /**
     * @return MorphMany
     */
    public function searchHistories(): MorphMany
    {
        return $this->morphMany(SearchHistory::class, 'searchable');
    }
}