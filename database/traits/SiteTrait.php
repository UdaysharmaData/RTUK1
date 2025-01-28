<?php

namespace Database\Traits;

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
        return Site::where('domain', env('X_Seeded_Site'))->first();
    }
}