<?php

namespace App\Modules\User\Policies;

use App\Modules\User\Models\Profile;
use App\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function updatePassword(User $user): Response|bool
    {
        return $this->getResponse($user);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Modules\User\Models\Profile  $profile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Profile $profile)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Modules\User\Models\Profile  $profile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Profile $profile)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Modules\User\Models\Profile  $profile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Profile $profile)
    {
        //
    }

    /**
     * @param User $user
     * @param string|null $message
     * @return Response
     */
    private function getResponse(User $user, string $message = null): Response
    {
        return $user->isAdmin() || $user->id === request()->user()?->id
            ? Response::allow()
            : Response::deny($message ?: 'You are not the owner of this profile.');
    }
}
