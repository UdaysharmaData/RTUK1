<?php

namespace App\Traits\Metable;

use App\Models\Meta;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasManyMeta
{
    public function meta(): MorphMany
    {
        return $this->morphMany(Meta::class, 'metable');
    }

    /**
     * Delete (cascade) the polymorphic relationship upon model forceDelete.
     * 
     * @return void
     */
    public static function bootHasManyMeta(): void
    {
        $model = new static;

        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) { // TODO: Replace with method_exists(static::class, 'bootSoftDeletes')
            static::deleted(function ($model) {
                if ($model->meta->count() > 0) { // Delete the meta associated with the record
                    $model->meta->each(function ($meta) { $meta->delete(); });
                }
            });
        }

        if (method_exists($model, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                if ($model->meta->count() > 0) { // Delete the meta associated with the record
                    $model->meta->each(function ($meta) { $meta->delete(); });
                }
            });
        }
    }
}
