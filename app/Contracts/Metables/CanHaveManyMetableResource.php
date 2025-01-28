<?php

namespace App\Contracts\Metables;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManyMetableResource
{
    /**
     * @return MorphMany
     */
    public function meta(): MorphMany;
}
