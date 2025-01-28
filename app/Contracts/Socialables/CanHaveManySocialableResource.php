<?php

namespace App\Contracts\Socialables;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManySocialableResource
{
    /**
     * @return MorphMany
     */
    public function socials(): MorphMany;
}
