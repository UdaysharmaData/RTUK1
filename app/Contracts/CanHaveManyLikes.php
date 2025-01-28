<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManyLikes
{
    /**
     * @return MorphMany
     */
    public function likes(): MorphMany;
}
