<?php

namespace App\Traits\Locationable;

use App\Models\Location;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasOneLocation
{
    public function location(): MorphOne
    {
        return $this->morphOne(Location::class, 'locationable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     *
     * @return void
     */
    public static function bootHasOneLocation(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                $model->location?->delete(); // Delete the location associated with the record
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                $model->location?->delete(); // Delete the location associated with the record
            });
        }
    }
}
