<?php

namespace App\Modules\User\Models;

use App\Contracts\FilterableListQuery;
use App\Contracts\Invoiceables\CanHaveManyInvoiceableResource;
use App\Contracts\Socialables\CanHaveManySocialableResource;
use App\Enums\RoleNameEnum;
use App\Enums\SiteUserStatus;
use App\Enums\VerificationCodeTypeEnum;
use App\Models\PaymentCard;
use App\Modules\Event\Models\EventManagerDownload;
use App\Modules\User\Contracts\CanHaveManyPermissions;
use App\Modules\User\Contracts\CanHaveManyRoles;
use App\Modules\User\Contracts\CanHaveManyTwoFactorAuthMethods;
use App\Modules\User\Contracts\CanReferByCode;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use App\Modules\User\Models\Relations\UserRelations;
use App\Modules\User\Traits\CustomRouteNotifications;
use App\Modules\User\Traits\HasConnectedDevices;
use App\Modules\User\Traits\HasManyPermissions;
use App\Modules\User\Traits\HasManyRoles;
use App\Modules\User\Traits\ReferralCodeTrait;
use App\Scopes\SiteUserScope;
use App\Services\Auth\Traits\MustVerifyAccountEmail;
use App\Services\Auth\Traits\PasswordSetUp;
use App\Services\Auth\Traits\SendPasswordResetNotification;
use App\Services\DataCaching\Traits\CacheQueryBuilder;
use App\Services\PasswordProtectionPolicy\Contracts\KeepPasswordHistory;
use App\Services\PasswordProtectionPolicy\Traits\PasswordHistory;
use App\Services\SoftDeleteable\Contracts\SoftDeleteableContract;
use App\Services\SoftDeleteable\Traits\ActionMessages;
use App\Services\TwoFactorAuth\TwoFactorAuthService;
use App\Traits\AddUuidRefAttribute;
use App\Traits\ClientIdAttributeGenerator;
use App\Traits\FilterableListQueryScope;
use App\Traits\Invoiceable\HasManyInvoices;
use App\Traits\Socialable\HasManySocials;
use App\Traits\UuidRouteKeyNameTrait;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
    implements
    MustVerifyEmail,
    KeepPasswordHistory,
    CanReferByCode,
    CanHaveManyRoles,
    CanHaveManyPermissions,
    CanUseCustomRouteKeyName,
    CanHaveManySocialableResource,
    CanHaveManyInvoiceableResource,
    CanHaveManyTwoFactorAuthMethods,
    FilterableListQuery,
    SoftDeleteableContract
{
    use HasApiTokens,
        HasFactory,
        SoftDeletes,
        Notifiable,
        CustomRouteNotifications,
        MustVerifyAccountEmail,
        SendPasswordResetNotification,
        TwoFactorAuthService,
        PasswordHistory,
        ReferralCodeTrait,
        HasManyRoles,
        HasManyPermissions,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        HasManySocials,
        UserRelations,
        HasManyInvoices,
        HasConnectedDevices,
        ClientIdAttributeGenerator,
        FilterableListQueryScope,
        ActionMessages,
        CacheQueryBuilder;

    /**
     * If User implements MustVerifyEmail, use MustVerifyAccountEmail trait
     * If User implements MustVerifyPhone, use MustVerifyAccountPhone trait
     */

    /**
     * password protection policy configuration
     */
    public $pppConfig;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if ((new \ReflectionClass(self::class))
            ->implementsInterface(KeepPasswordHistory::class)) {
            $this->pppConfig = config('passwordprotectionpolicy');
        }
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'remember_token',
        'temp_pass',
        'email',
        'phone',
        'stripe_customer_id',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'full_name',
        'is_verified',
//        'user_active_role',
        'is_password_set'
    ];

    /**
     * @var string[]
     */
    protected $with = [
//        'profile',
//        'activeRole',
//        'roles',
//        'permissions'
    ];

    /**
     * system genders
     */
    const GENDER = [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other'
    ];

    const RoleDefaultPermissions = [
        'Participant' => [
            'can_manage_registrations',
        ]
    ];

    /**
     * @var string[]
     */
    public static $actionMessages = [
        'force_delete' => 'Deleting a user will unlink it from events and other associated services within the platform.'
    ];

    /**
     * @param $request
     * @return mixed
     */
    public static function createNew($request)
    {
        return self::create($request);
    }

    /**
     * @return string
     */
    public function getRef(): string
    {
        return $this->attributes['ref'];
    }

    /**
     * @param VerificationCodeTypeEnum $typeEnum
     * @return string
     */
    public function generateVerificationCode(VerificationCodeTypeEnum $typeEnum): string
    {
        return $this->verificationCodes()->create([
            'type' => $typeEnum->value
        ])?->code;
    }

    /**
     * @return string|null
     */
    public function latestVerificationCode(): string|null
    {
        return $this->verificationCodes()->active()->latest()->first()?->code;
    }

    /**
     * @return Attribute
     */
    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => "$this->first_name $this->last_name",
        );
    }

    public function salutationName(): Attribute
    {
        return Attribute::make(
            get: fn() => ucfirst($this->first_name) ?: ucfirst($this->last_name)?: ''
        );
    }

    /**
     * Check whether the user has access to the site or not.
     *
     * @return Attribute
     */
    public function hasAccess(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $userCurrentSite = $this->sites()
                    ->where('site_id', '=', clientSiteId())
                    ->first();

                if (is_null($userCurrentSite)) {
                    $this->sites()->syncWithoutDetaching([clientSiteId()]);
                    return true;
                }

                return $userCurrentSite->pivot?->status?->value === SiteUserStatus::Active->value;
            },
        );
    }

    /**
     * @return Attribute
     */
    public function isVerified(): Attribute
    {
        return Attribute::make(
            get: fn () => ((! is_null($this->email_verified_at))
                || (! is_null($this->phone_verified_at))),
        );
    }

    /**
     * @return Attribute
     */
    public function isPasswordSet(): Attribute
    {
        return Attribute::make(
            get: fn () => (! is_null($this->password)),
        );
    }

    /**
     * @return HasMany
     */
    public function paymentCards(): HasMany
    {
        return $this->hasMany(PaymentCard::class);
    }

    /**
     * get the different two-factor auth methods set by the user
     * @return BelongsToMany
     */
    public function twoFactorAuthMethods(): BelongsToMany
    {
        return $this->belongsToMany(TwoFactorAuthMethod::class, TwoFactorAuthUser::class)
            ->orderBy('default', 'desc');
    }

    /**
     * @return HasMany
     */
    public function twoFactorAuthUsers(): HasMany
    {
        return $this->hasMany(TwoFactorAuthUser::class);
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param string $name
     * @param array $scopes
     * @return \Laravel\Passport\PersonalAccessTokenResult
     * @throws BindingResolutionException
     */
    public function createToken($name, array $scopes = []): \Laravel\Passport\PersonalAccessTokenResult
    {
        return Container::getInstance()
            ->make(\App\Modules\User\Factories\Passport\PersonalAccessTokenFactory::class)
            ->make($this->getKey(), $name, $scopes);
    }

    /**
     * @return User|null
     */
    public function getUserAndConnectedDevices(): ?User
    {
        return $this->fresh()
//            ->load(['connectedDevices' => function ($query) {
//                $query->whereNotNull('device');
//            }])
            ;
    }

    /**
     * @param RoleNameEnum $roleName
     * @param bool $syncToCurrentSite
     * @return User
     * @throws Exception
     */
    public function bootstrapUserRelatedProperties(RoleNameEnum $roleName = RoleNameEnum::Participant, Bool $syncToCurrentSite = true): static
    {
        if (request()?->route()?->getName() !== 'portal.admin.create.user') {
            $this->assignRole($roleName);
            $profile = $this->profile()->updateOrCreate([], []);

            if ($roleName->value == RoleNameEnum::Participant->value) {
                $profile->participantProfile()->updateOrCreate([], []);
            }

            $uri = \Illuminate\Support\Facades\Route::getFacadeRoot()?->current()?->uri();


            if (! $uri || ! \Illuminate\Support\Str::contains($uri, 'payment')) { // Don't run this during payment as it will change the user's active role. TODO: Enhance this for other operations related to already created users
                if ($this->created_at->isToday() || !$this->activeRole) { // Only reset the user's active role if they were created today.
                    $this->assignDefaultActiveRole();
                }
            }

            $this->grantRoleDefaultPermissions();
            // $this->saveReferralCode();
        }

        if (clientSiteId() && $syncToCurrentSite) {
            $this->sites()->syncWithoutDetaching([clientSiteId()]);
        }

        return $this;
    }

    /**
     * @return void
     */
    public static function bootUseSiteGlobalScope(): void
    {
        static::addGlobalScope(new SiteUserScope);
    }

    /**
     * @return Attribute
     */
    public function status(): Attribute
    {
        return Attribute::make(
            get: fn () => (($this->sites?->first()?->pivot['status'])->value) ?? SiteUserStatus::Active->value,
        );
    }

    /**
     * @param $query
     * @param int|null $siteId
     * @return void
     */
    public function scopeCurrentSiteOnly($query, int $siteId = null): void
    {
        $query->whereHas('sites', function ($query) use ($siteId) {
            $query->where('site_user.site_id', '=', $siteId ?? clientSiteId());
        });
    }
}
