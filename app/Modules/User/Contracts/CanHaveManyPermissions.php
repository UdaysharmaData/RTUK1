<?php

namespace App\Modules\User\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface CanHaveManyPermissions
{
    public function permissions(): BelongsToMany;
}
