<?php

namespace App\Traits;

trait AssociateMetadata
{
    /**
     * @return void
     */
    public static function bootAssociateMetadata(): void
    {
        static::created(function ($model) {
            $model->metadata()->create();
        });
    }
}
