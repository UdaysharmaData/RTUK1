<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManyComments
{
    /**
     * @return MorphMany
     */
    public function comments(): MorphMany;
}
