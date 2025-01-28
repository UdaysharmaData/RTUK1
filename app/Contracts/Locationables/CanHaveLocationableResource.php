<?php

namespace App\Contracts\Locationables;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface CanHaveLocationableResource
{
    /**
     * @return MorphOne
     */
    public function location() :MorphOne;
}
