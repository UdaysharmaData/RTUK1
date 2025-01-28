<?php

namespace App\Modules\User\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface CanHaveManyRoles
{
    public function roles(): BelongsToMany;
}
