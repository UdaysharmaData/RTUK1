<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait AddUuidRefAttribute
{
    /**
     * @param $model
     * @return void
     */
    public static function addUuidRefAttributeToModel($model): void
    {
        $model->ref = Str::orderedUuid();
    }

    /**
     * @return void
     */
    public static function bootAddUuidRefAttribute(): void
    {
        static::creating(function ($model) {
            self::addUuidRefAttributeToModel($model);
        });
    }
}
