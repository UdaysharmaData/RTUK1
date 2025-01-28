<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait AttachableEntity
{
    /**
     * @return MorphMany
     */
    public function attachables(): MorphMany
    {
        return $this->morphMany(AttachableEntity::class, 'attachable');
    }
}
