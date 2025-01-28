<?php

namespace App\Traits;

trait SiteIdAttributeGenerator
{
    /**
     * @return void
     */
    protected static function bootSiteIdAttributeGenerator(): void
    {
        static::creating(function ($model) {
            $model->site_id = $model->site_id ?? clientSiteId();
        });
    }
}
