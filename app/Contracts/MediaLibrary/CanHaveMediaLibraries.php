<?php

namespace App\Contracts\MediaLibrary;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveMediaLibraries
{
    /**
     * @return MorphMany
     */
    public function mediaLibraries(): MorphMany;
}
