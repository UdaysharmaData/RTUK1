<?php

namespace App\Modules\Setting\Models\Traits;

use App\Models\Sitemap;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasManySitemaps
{
    /**
     * Get the sitemaps associated with the site.
     * 
     * @return HasMany
     */
    public function sitemaps(): HasMany
    {
        return $this->hasMany(Sitemap::class);
    }
}
