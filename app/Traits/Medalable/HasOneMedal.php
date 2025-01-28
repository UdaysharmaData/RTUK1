<?php

namespace App\Traits\Medalable;

use Illuminate\Database\Eloquent\Relations\MorphOne;

use App\Models\Medal;

trait HasOneMedal
{

    /**
     * Get the medal
     * 
     * @return MorphOne
     */
    public function medal():MorphOne
    {
        return $this->morphOne(Medal::class, 'medalable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     *
     * @return void
     */
    public static function bootHasOneMedal(): void
    {
        $model = new static;

        if (!in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) {
            static::deleted(function ($model) {
                $model->medal?->delete();
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->medal?->delete();
            });
        }
    }
}
