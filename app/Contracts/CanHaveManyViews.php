<?php

namespace App\Contracts;

use App\Services\Analytics\Contracts\AnalyzableInterface;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManyViews extends AnalyzableInterface
{
    /**
     * @return MorphMany
     */
    public function views(): MorphMany;
}
