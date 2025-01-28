<?php

namespace App\Modules\User\Traits;

use App\Enums\RoleNameEnum;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasManyPermissions
{
    /**
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    /**
     * @return mixed
     */
    public function permits(): mixed
    {
        return $this->permissions->pluck('name')->unique();
    }

    /**
     * Give Permission
     *
     * @param int|string|Model $permission
     * @return void
     */
    public function grant(int|string|Model $permission): void
    {
        $this->permissions()->syncWithoutDetaching($this->getPermission($permission));
    }

    /**
     * Revoke permission
     *
     * @param int|string|Model $permission
     * @return void
     */
    public function revoke(int|string|Model $permission): void
    {
        $this->permissions()->detach($this->getPermission($permission));
    }

    /**
     * Check if the user has a permission
     *
     * @param int|string $permission
     * @return boolean
     */
    public function hasPermission(int|string $permission): bool
    {
        return $this->permits()->contains($permission);
    }

    /**
     * @param int|string|Model $permission
     * @return mixed
     */
    private function getPermission(int|string|Model $permission): mixed
    {
        if (is_string($permission)) {
            return Permission::whereName($permission)->firstOrFail();
        }

        if (is_int($permission)) {
            return Permission::findOrFail($permission);
        }

        if ($permission instanceof Model) {
            return $permission;
        }
        return null;
    }

    /**
     * @return User
     */
    public function grantRoleDefaultPermissions(): User
    {
        foreach ($this->roles as $role) {
            if (isset(User::RoleDefaultPermissions[$role->name->name])) {
                foreach (User::RoleDefaultPermissions[$role->name->name] as $permission) {
                    $this->grant($permission);
                }
            }
        }

        return $this;
    }
}
