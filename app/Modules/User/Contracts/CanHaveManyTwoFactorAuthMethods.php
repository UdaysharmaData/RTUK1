<?php

namespace App\Modules\User\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface CanHaveManyTwoFactorAuthMethods
{

    public function twoFactorAuthMethods(): BelongsToMany;
}
