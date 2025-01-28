<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface CanHaveAnalyticsMetadata
{
    /**
     * @return MorphOne
     */
    public function metadata(): MorphOne;
}
