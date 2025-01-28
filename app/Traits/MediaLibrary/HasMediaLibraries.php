<?php

namespace App\Traits\MediaLibrary;

use App\Models\MediaLibrary;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMediaLibraries
{
    /**
     * @return MorphMany
     */
    public function mediaLibraries(): MorphMany
    {
        return $this->morphMany(MediaLibrary::class, 'mediable');
    }
}
