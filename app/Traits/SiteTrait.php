<?php

namespace App\Traits;

use App\Modules\Setting\Models\Site;

trait SiteTrait
{
    /**
     * Get the site making the request.
     *
     * @return ?Site
     */
    protected static function getSite(): ?Site
    {
        if (app()->runningInConsole()) {
            return siteSetting();
        }

        return clientSite();
    }
}