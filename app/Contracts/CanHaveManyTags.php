<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManyTags
{
    /**
     * @return MorphMany
     */
    public function tags(): MorphMany;
}
