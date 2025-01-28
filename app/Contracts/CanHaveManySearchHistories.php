<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManySearchHistories
{
    /**
     * @return MorphMany
     */
    public function searchHistories(): MorphMany;
}