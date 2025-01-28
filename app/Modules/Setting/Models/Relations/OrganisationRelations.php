<?php

namespace App\Modules\Setting\Models\Relations;

use App\Modules\Setting\Models\Site;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait OrganisationRelations
{
    /**
     * Get the sites associated with the organisation.
     * 
     * @return HasMany
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }
}
