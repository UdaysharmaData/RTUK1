<?php

namespace App\Modules\User\Traits;

use App\Modules\Setting\Enums\SiteEnum;
use App\Traits\SiteTrait;
use App\Enums\RoleNameEnum;
use App\Http\Helpers\AccountType;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Modules\User\Models\ActiveRole;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasManyRoles
{
    use SiteTrait;

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
    public function titles(): mixed
    {
        return $this->roles
            ->pluck('name')
            ->unique()
            ->map->value;
    }

    /**
     * @param int|string|RoleNameEnum $role
     * @return HasManyRoles
     */
    public function assignRole(int|string|RoleNameEnum $role): static
    {
        $this->syncRolesOnCurrentSite([$this->getRole($role)->id], false);

        return $this;
    }

    /**
     * @param int|string|RoleNameEnum $role
     * @return HasManyRoles
     */
    public function unassignRole(int|string|RoleNameEnum $role): static
    {
        $this->detachRolesOnCurrentSite([$this->getRole($role)->id]);

        return $this;
    }

    /**
     * @param int|string|RoleNameEnum $role
     * @return mixed
     */
    private function getRole(int|string|RoleNameEnum $role): mixed
    {
        $match = null;

        if ($role instanceof RoleNameEnum) {
            $match = Role::whereName($role->value)->firstOrFail();
        }
        if (is_string($role)) {
            $match = Role::whereName($role)->firstOrFail();
        }
        if (is_int($role)) {
            $match = Role::findOrFail($role);
        }

        return $match;
    }

    /**
     * @return mixed
     */
    public function isAdmin(): bool
    {
        return  $this->isRole(RoleNameEnum::Administrator->value);
    }

    /**
     * @return mixed
     */
    public function isGeneralAdmin(): bool
    {
        return ($this->isAdmin() && static::getSite()?->domain == SiteEnum::generalSite()->value);
    }

    /**
     * @return bool
     */
    public function isOnGeneralSite(): bool
    {
        return clientSite()?->domain === SiteEnum::generalSite()->value;
    }

    /**
     * @return mixed
     */
    public function isDeveloper(): bool
    {
        return $this->isRole(RoleNameEnum::Developer->value);
    }

    /**
     * @return mixed
     */
    public function isAccountManager(): bool
    {
        return $this->isRole(RoleNameEnum::AccountManager->value);
    }

    /**
     * @return mixed
     */
    public function isEventManager(): bool
    {
        return $this->isRole(RoleNameEnum::EventManager->value);
    }

    /**
     * @return mixed
     */
    public function isCharityOwner(): bool
    {
        return $this->isRole(RoleNameEnum::Charity->value);
    }

    /**
     * @return mixed
     */
    public function isCharityUser(): bool
    {
        return $this->isRole(RoleNameEnum::CharityUser->value);
    }

    /**
     * @return mixed
     */
    public function isParticipant(): bool
    {
        return $this->isRole(RoleNameEnum::Participant->value);
    }

    /**
     * @return string|null
     */
    public function activeRoleName(): ?string
    {
        return $this->activeRole?->role?->name?->value;
    }

    /**
     * @return mixed
     */
    public function hasRole(string|RoleNameEnum $role): bool
    {
        if ($role instanceof RoleNameEnum) {
            $needle = $role->value;
        } else $needle = $role;

        return $this->titles()->contains($needle);
    }

    /**
     * @return HasOne
     */
    public function activeRole(): HasOne
    {
        return $this->hasOne(ActiveRole::class)
            ->where('site_id', clientSiteId());
//            ->join(
//                'roles',
//                'roles.id',
//                '=',
//                'active_roles.role_id',
//            );
    }

    /**
     * @return \App\Modules\User\Models\User
     * @throws \Exception
     */
    public function assignDefaultActiveRole(): \App\Modules\User\Models\User
    {
        if ($this->hasRole($role = RoleNameEnum::Administrator)) {
            $user = $this->assignActiveRole($role);
        } elseif ($this->hasRole($role = RoleNameEnum::Developer)) {
            $user = $this->assignActiveRole($role);
        } elseif ($this->hasRole($role = RoleNameEnum::AccountManager)) {
            $user = $this->assignActiveRole($role);
        } elseif ($this->hasRole($role = RoleNameEnum::EventManager)) {
            $user = $this->assignActiveRole($role);
        } elseif ($this->hasRole($role = RoleNameEnum::Charity)) {
            $user = $this->assignActiveRole($role);
        } elseif ($this->hasRole($role = RoleNameEnum::CharityUser)) {
            $user = $this->assignActiveRole($role);
        } else {
            $user = $this->assignActiveRole(RoleNameEnum::Participant);
        }
        return $user;
    }

    /**
     * @return Attribute
     */
    public function userActiveRole(): Attribute
    {
//        $this->load(['activeRole']);
        {
            return Attribute::make(
                get: fn () => $this->activeRole->role
            );
        }
    }

    /**
     * @param string $roleName
     * @return bool
     */
    private function isRole(string $roleName): bool
    {
        return $this->activeRoleName() === $roleName;
    }

    /**
     * @param RoleNameEnum $roleTitleEnum
     * @return \App\Modules\User\Models\User
     * @throws \Exception
     */
    private function assignActiveRole(RoleNameEnum $roleTitleEnum): \App\Modules\User\Models\User
    {
        try {
            $this->activeRole()->updateOrCreate([
                'site_id' => clientSiteId(),
            ], [
                'role_id' => Role::firstWhere('name', $roleTitleEnum->value)?->id
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
        }

        return $this->refresh();
    }

    /**
     * Get the roles of the user by order of importance.
     *
     * @param  User  $user
     * @return array
     */
    public static function orderUserRoles(User $user): array
    {
        $user->load('roles');

        $roles = [];

        foreach (RoleNameEnum::cases() as $role) {
            if (in_array($role, $user->roles->pluck('name')->all()))
                array_push($roles, $role->value);
        }

        return $roles;
    }

    /**
     * @param array $ids
     * @param bool $detach
     * @param int|null $siteId
     * @return HasManyRoles|User
     */
    public function syncRolesOnCurrentSite(array $ids, bool $detach = true, int $siteId = null): self
    {
        if (count($ids) > 0) {
            $this->roles()
                ->wherePivot('site_id', '=', $siteId = $siteId ?? clientSiteId())
                ->syncWithPivotValues($ids, ['site_id' => $siteId], $detach);
        }

        return $this;
    }

    /**
     * @param array $ids
     * @param int|null $siteId
     * @return HasManyRoles|User
     */
    public function detachRolesOnCurrentSite(array $ids, int $siteId = null): self
    {
        $this->roles()
            ->wherePivot('site_id', '=', $siteId ?? clientSiteId())
            ->detach($ids);

        return $this;
    }
}
