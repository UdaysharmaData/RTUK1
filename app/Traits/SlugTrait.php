<?php

namespace App\Traits;

use Illuminate\Support\Str;
use App\Traits\SlugGenerator;
use App\Modules\Event\Models\Event;

trait SlugTrait
{
    use SlugGenerator;

    // /**
    //  * @param $model
    //  * @return mixed
    //  */
    // protected static function addSlugAttributeToModel($model): mixed
    // {
    //     $model->slug = Str::slug($model->title);

    //     return $model;
    // }

    /**
     * The "boot" method of the trait.
     *
     * @return void
     */
    protected static function bootSlugTrait(): void
    {
        static::creating(function ($model) {
            if (method_exists($model, 'sluggable')) {

                foreach ($model->sluggable() as $attribute => $config) {
                    $model->{$attribute} = static::getUniqueSlug($model->{$config['source']} ?? $model[self::slugAttribute()], null, $model->site_id ?? null, $attribute);
                }
            } else if ($model instanceof Event) { // Slug is not unique for events
                $model->slug = Str::slug($model->name);
            }  else {
                $model->slug = static::getUniqueSlug($model->getAttributes()['name'] ?? $model[self::slugAttribute()], null, $model->site_id ?? null);
            }
        });

        static::updating(function ($model) {
            if (method_exists($model, 'sluggable')) {
                foreach ($model->sluggable() as $attribute => $config) {
                    if (!$model->isDirty($attribute) && $model->isDirty($config['source'])) {
                        $model->{$attribute} = static::getUniqueSlug($model->{$config['source']}, $model->id, $model->site_id ?? null, $attribute);
                    }
                }
            } else if ($model instanceof Event) { // Slug is not unique for events
                $model->slug = Str::slug($model->name);
            } else {
                $model->slug = static::getUniqueSlug($model->getAttributes()['name'] ?? $model[self::slugAttribute()], $model->id, $model->site_id ?? null);
            }
        });
    }
}
