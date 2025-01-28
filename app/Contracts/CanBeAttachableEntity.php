<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanBeAttachableEntity
{
    /**
     * @return MorphMany
     */
    public function attachables(): MorphMany;
}
