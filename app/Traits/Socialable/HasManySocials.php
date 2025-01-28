<?php

namespace App\Traits\Socialable;

use App\Models\Social;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasManySocials
{
    public function socials(): MorphMany
    {
        return $this->morphMany(Social::class, 'socialable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     * 
     * @return void
     */
    public static function bootHasManySocials(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                $model->socials()->delete(); // Delete the socials associated with the record
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->socials()->delete(); // Delete the socials associated with the record
            });
        }
    }
}
