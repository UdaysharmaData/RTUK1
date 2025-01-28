<?php

namespace App\Traits\Metable;

use App\Models\Meta;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasOneMeta
{
    public function meta() :MorphOne
    {
        return $this->morphOne(Meta::class, 'metable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     *
     * @return void
     */
    public static function bootHasOneMeta(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                $model->meta()->delete(); // Delete the meta associated with the record
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->meta()->delete(); // Delete the meta associated with the record
            });
        }
    }
}
