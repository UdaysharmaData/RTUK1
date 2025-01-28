<?php

namespace App\Traits;

trait AddActiveRoleIdAttribute
{
    /**
     * @return void
     */
    public static function bootAddActiveRoleIdAttribute(): void
    {
        static::creating(function ($model) {
            $model->role_id = request()?->user()?->activeRole?->role?->id;;
        });
    }
}
