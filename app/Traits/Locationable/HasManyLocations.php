<?php

namespace App\Traits\Locationable;

use App\Models\Location;
use App\Enums\LocationUseAsEnum;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasManyLocations
{
    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locationable');
    }

    /**
     * Get the address.
     * 
     * @return MorphOne
     */
    public function address(): MorphOne
    {
        return $this->morphOne(Location::class, 'locationable')->where('use_as', LocationUseAsEnum::Address)->latest('id');
    }

    // /**
    //  * Get the routes.
    //  * NOTE: Kept for furture use. This represent the event route info and it gets plotted from a series of coordinates (lat & lng)
    //  * 
    //  * @return MorphMany
    //  */
    // public function routes(): MorphMany
    // {
    //     return $this->morphMany(Location::class, 'locationable')->where('use_as', LocationUseAsEnum::Route);
    // }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     * 
     * @return void
     */
    public static function bootHasManyLocations(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                if ($model->locations->count() > 0) { // Delete the locations associated with the record
                    $model->locations->each(function ($location) { $location->delete(); });
                }
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                if ($model->locations->count() > 0) { // Delete the locations associated with the record
                    $model->locations->each(function ($location) { $location->delete(); });
                }
            });
        }
    }
}
