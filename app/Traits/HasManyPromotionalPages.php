<?php

namespace App\Traits;

use App\Modules\Event\Models\PromotionalPage;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasManyPromotionalPages
{
    /**
     * Get the promotional pages associated with the region
     *
     * @return HasMany
     */
    public function promotionalPages(): HasMany
    {
        return $this->hasMany(PromotionalPage::class);
    }
}
