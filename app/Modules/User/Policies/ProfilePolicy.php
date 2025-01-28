<?php

namespace App\Modules\User\Policies;

use App\Modules\User\Models\Profile;
use App\Modules\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ProfilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Modules\User\Models\Profile  $profile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Profile $profile)
    {
        return $this->getResponse($user, $profile);
    }

    /**
     * Determine whether the user can update profile avatar.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Modules\User\Models\Profile  $profile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function updateAvatar(User $user, Profile $profile): Response|bool
    {
        return $this->getResponse($user, $profile);
    }

    /**
     * Determine whether the user can delete a profile avatar.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Modules\User\Models\Profile  $profile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function deleteAvatar(User $user, Profile $profile): Response|bool
    {
        return $this->getResponse($user, $profile);
    }

    /**
     * Determine whether the user can update profile background image.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Modules\User\Models\Profile  $profile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function updateBackgroundImage(User $user, Profile $profile): Response|bool
    {
        return $this->getResponse($user, $profile);
    }

    /**
     * Determine whether the user can delete a profile background image.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Modules\User\Models\Profile  $profile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function deleteBackgroundImage(User $user, Profile $profile): Response|bool
    {
        return $this->getResponse($user, $profile);
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
     * Determine whether the user can update the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Modules\User\Models\Profile  $profile
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Profile $profile)
    {
        return $this->getResponse($user, $profile);
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
     * @param Profile $profile
     * @param string|null $message
     * @return Response
     */
    private function getResponse(User $user, Profile $profile, string $message = null): Response
    {
        return $user->id === $profile->user_id || request()->user()->isAdmin()
            ? Response::allow()
            : Response::deny($message ?: 'You are not the owner of this profile.');
    }
}
