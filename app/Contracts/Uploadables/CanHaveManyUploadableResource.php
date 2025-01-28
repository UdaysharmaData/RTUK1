<?php

namespace App\Contracts\Uploadables;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManyUploadableResource
{
    /**
     * @return HasManyThrough
     */
    public function uploads(): HasManyThrough;
    
    /**
     * uploadables
     *
     * @return MorphMany
     */
    public function uploadables(): MorphMany;
}
