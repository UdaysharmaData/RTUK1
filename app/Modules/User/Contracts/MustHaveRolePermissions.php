<?php

namespace App\Modules\User\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface MustHaveRolePermissions
{
    /**
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany;
}
