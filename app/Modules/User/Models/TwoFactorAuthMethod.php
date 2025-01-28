<?php

namespace App\Modules\User\Models;

use App\Enums\BoolActiveInactiveEnum;
use App\Traits\AddUuidRefAttribute;
use App\Traits\UuidRouteKeyNameTrait;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class TwoFactorAuthMethod extends Model implements CanUseCustomRouteKeyName
{
    use HasFactory, UuidRouteKeyNameTrait, AddUuidRefAttribute;

    protected $table = 'two_factor_auth_methods';

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_active'
    ];

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, TwoFactorAuthUser::class);
    }

    /**
     * Check if a 2-factor auth method has been enabled by a user
     * @param User $user
     * @return false
     */
    public function isEnabledBy(User $user): bool
    {
        return $this->users ? ($this->users->contains($user)) : false;
    }

    /**
     * @return HasMany
     */
    public function twoFactorAuthUsers(): HasMany
    {
        return $this->hasMany(TwoFactorAuthUser::class);
    }

    /**
     * @param User $user
     * @return false|HigherOrderBuilderProxy|mixed
     */
    public function isDefault(User $user): mixed
    {
        $twoFactorAuthUser = $this->twoFactorAuthUsers()->where('user_id', $user->id)->first();

        return $twoFactorAuthUser ? $twoFactorAuthUser->default : false;
    }

    /**
     * get active 2-factor auth methods
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', BoolActiveInactiveEnum::Active);
    }

    public function driver()
    {

    }

}
