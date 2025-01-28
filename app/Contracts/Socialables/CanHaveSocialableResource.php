<?php

namespace App\Contracts\Socialables;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface CanHaveSocialableResource
{
    /**
     * @return MorphOne
     */
    public function social() :MorphOne;
}
