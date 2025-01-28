<?php

namespace App\Traits;

use App\Models\AnalyticsMetadata;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasAnalyticsMetadata
{
    use AssociateMetadata;

    /**
     * @return MorphOne
     */
    public function metadata(): MorphOne
    {
        return $this->morphOne(AnalyticsMetadata::class, 'metadata');
    }
}
