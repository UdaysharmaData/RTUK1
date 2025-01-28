<?php

namespace App\Traits;

trait AddAuthorIdAttribute
{
    /**
     * @return void
     */
    public static function bootAddAuthorIdAttribute(): void
    {
        static::creating(function ($model) {
            $model->author_id = request()?->user()?->id;
        });
    }
}
