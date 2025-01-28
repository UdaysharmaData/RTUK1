<?php

namespace App\Modules\User\Traits;

use App\Enums\RoleNameEnum;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait RolePermissionsTrait
{
    /**
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * @return mixed
     */
    public function permissions(): mixed
    {
        return $this->roles->map->permissions->flatten()->pluck('name')->unique();
    }

    /**
     * @return mixed
     */
    public function titles(): mixed
    {
        return $this->roles
            ->pluck('name')
            ->unique()
            ->map->value;
    }

    /**
     * @param int|string|RoleNameEnum $role
     * @return RolePermissionsTrait
     */
    public function assignRole(int|string|RoleNameEnum $role): static
    {
        if ($role instanceof RoleNameEnum) {
            $role = Role::whereName($role->value)->firstOrFail();
        }
        if (is_string($role)) {
            $role = Role::whereName($role)->firstOrFail();
        }
        if (is_int($role)) {
            $role = Role::findOrFail($role);
        }
//        $this->roles()->sync($role, false);
        $this->roles()->attach($role);

        return $this;
    }

    /**
     * @param int|string|RoleNameEnum $role
     * @return RolePermissionsTrait
     */
    public function unassignRole(int|string|RoleNameEnum $role): static
    {
        if ($role instanceof RoleNameEnum) {
            $role = Role::whereName($role->value)->firstOrFail();
        }
        if (is_string($role)) {
            $role = Role::whereName($role)->firstOrFail();
        }
        if (is_int($role)) {
            $role = Role::findOrFail($role);
        }
//        $this->roles()->sync($role, false);

        $this->roles()->detach($role);

        return $this;
    }

    /**
     * @return mixed
     */
    public function isAdmin(): bool
    {
        return $this->titles()->contains(RoleNameEnum::Administrator->value);
    }

    /**
     * @return mixed
     */
    public function isDeveloper(): bool
    {
        return $this->titles()->contains(RoleNameEnum::Developer->value);
    }

    /**
     * @return mixed
     */
    public function isAccountManager(): bool
    {
        return $this->titles()->contains(RoleNameEnum::AccountManager->value);
    }

    /**
     * @return mixed
     */
    public function isEventManager(): bool
    {
        return $this->titles()->contains(RoleNameEnum::EventManager->value);
    }

    /**
     * @return mixed
     */
    public function isCharityOwner(): bool
    {
        return $this->titles()->contains(RoleNameEnum::Charity->value);
    }

    /**
     * @return mixed
     */
    public function isCharityUser(): bool
    {
        return $this->titles()->contains(RoleNameEnum::CharityUser->value);
    }

    /**
     * @return mixed
     */
    public function isParticipant(): bool
    {
        return $this->titles()->contains(RoleNameEnum::Participant->value);
    }

    /**
     * Check if the user has a permission
     *
     * @param string|null $permission
     * @return boolean
     */
    public function hasPermission(?string $permission): bool
    {
        return $this->permissions()->contains($permission);
    }
}
