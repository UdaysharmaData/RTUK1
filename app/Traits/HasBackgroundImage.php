<?php

namespace App\Traits;

use App\Models\BackgroundImage;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasBackgroundImage
{
    /**
     * @return HasOne
     */
    public function backgroundImage(): HasOne
    {
        return $this->hasOne(BackgroundImage::class, 'profile_id');
    }
}
