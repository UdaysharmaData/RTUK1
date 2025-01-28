<?php

namespace App\Traits;

trait AddUserIdAttribute
{
    /**
     * @return void
     */
    public static function bootAddUserIdAttribute(): void
    {
        static::creating(function ($model) {
            $model->user_id = request()?->user()?->id;
        });
    }
}
