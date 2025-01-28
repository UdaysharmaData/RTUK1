<?php

namespace App\Traits;

use App\Scopes\SiteScope;

trait UseSiteGlobalScope
{
    /**
     * @return void
     */
    public static function bootUseSiteGlobalScope(): void
    {
        static::addGlobalScope(new SiteScope);
    }
}
