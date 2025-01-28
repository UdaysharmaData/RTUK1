<?php

namespace App\Contracts\Locationables;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface CanHaveManyLocationableResource
{
    /**
     * @return MorphMany
     */
    public function locations(): MorphMany;

    /**
     * @return MorphOne
     */    
    public function address(): MorphOne;
}
