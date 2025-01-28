<?php

namespace App\Contracts\Metables;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface CanHaveMetableResource
{
    /**
     * @return MorphOne
     */
    public function meta() :MorphOne;
}
