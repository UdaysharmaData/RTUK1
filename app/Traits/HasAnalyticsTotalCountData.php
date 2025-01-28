<?php

namespace App\Traits;

use App\Models\AnalyticsTotalCount;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasAnalyticsTotalCountData
{
    /**
     * @return MorphOne
     */
    public function totalCount(): MorphOne
    {
        return $this->morphOne(AnalyticsTotalCount::class, 'countable');
    }
}
