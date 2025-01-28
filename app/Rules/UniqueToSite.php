<?php

namespace App\Rules;

use App\Modules\User\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Query\JoinClause;

class UniqueToSite implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return User::query()
            ->where('email', $value)
            ->join('site_user', function (JoinClause $join) {
                $join->on('users.id', '=', 'site_user.user_id')
                    ->where('site_user.site_id', clientSiteId());
            })->doesntExist();

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The email provided is already associated with an existing account on the platform.';
    }
}
