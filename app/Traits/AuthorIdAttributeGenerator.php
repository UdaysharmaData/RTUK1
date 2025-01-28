<?php

namespace App\Traits;

trait AuthorIdAttributeGenerator
{
    /**
     * @return void
     */
    protected static function bootAuthorIdAttributeGenerator(): void
    {
        static::creating(function ($model) {
            $model->author_id = $model->author_id ?? auth()->id();
        });
    }
}
