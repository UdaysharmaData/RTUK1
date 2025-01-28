<?php

namespace App\Traits;

trait AddRequestUserAttribute
{
    /**
     * @return void
     */
    public static function bootAddRequestUserAttribute(): void
    {
        static::creating(function ($model) {
            $model->user_id = request()->user()->id;
        });
    }
}
