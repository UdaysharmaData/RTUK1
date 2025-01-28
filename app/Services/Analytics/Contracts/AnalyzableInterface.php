<?php

namespace App\Services\Analytics\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface AnalyzableInterface {
    /**
     * @return MorphOne
     */
    public function totalCount(): MorphOne;
}
