<?php

namespace App\Traits\Medalable;

use App\Models\Medal;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasManyMedals
{

    /**
     * Get the medals
     * 
     * @return MorphMany
     */
    public function medals(): MorphMany
    {
        return $this->morphMany(Medal::class, 'medalable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     *
     * @return void
     */
    public static function bootHasManyMedals(): void
    {
        $model = new static;

        if (!in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) {
            static::deleted(function ($model) {
                foreach ($model->medals as $medal) {
                    $medal->delete();
                }
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                foreach ($model->medals as $medal) {
                    $medal->delete();
                }
            });
        }
    }
}
