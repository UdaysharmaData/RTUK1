<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface CanHaveAnalyticsTotalCountData
{
    /**
     * @return MorphOne
     */
    public function totalCount(): MorphOne;
}
