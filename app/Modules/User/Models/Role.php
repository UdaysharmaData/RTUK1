<?php

namespace App\Modules\User\Models;

use App\Contracts\FilterableListQuery;
use App\Enums\RoleNameEnum;
use App\Modules\Setting\Models\Site;
use App\Scopes\SiteUserScope;
use App\Services\DataCaching\Traits\CacheQueryBuilder;
use App\Services\SoftDeleteable\Contracts\SoftDeleteableContract;
use App\Services\SoftDeleteable\Traits\ActionMessages;
use App\Traits\AddUuidRefAttribute;
use App\Traits\BelongsToSite;
use App\Traits\FilterableListQueryScope;
use App\Traits\SiteIdAttributeGenerator;
use App\Traits\UseSiteGlobalScope;
use App\Traits\UuidRouteKeyNameTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Participant\Models\ParticipantAction;
use App\Modules\User\Contracts\CanUseCustomRouteKeyName;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model implements CanUseCustomRouteKeyName, FilterableListQuery, SoftDeleteableContract
{
    use HasFactory,
        SoftDeletes,
        UuidRouteKeyNameTrait,
        AddUuidRefAttribute,
        SiteIdAttributeGenerator,
        BelongsToSite,
        FilterableListQueryScope,
        ActionMessages,
        UseSiteGlobalScope,
        CacheQueryBuilder;

    /**
     * Assignable roles
     */
    const ROLES = ['developer', 'administrator', 'account_manager', 'participant', 'charity'];

    /**
     * @var string[]
     */
    public static $actionMessages = [
        'force_delete' => 'Permanently deleting a role will remove any associated privileges and permissions currently assigned to users on the platform.'
    ];

    /**
     * default validation rules
     * @var string[][]
     */
    const RULES = [
        'create_or_update' => [
            'name' => ['required', 'string', 'unique:roles'],
            'description' => ['nullable', 'string']
        ],
        'assign_or_unassign' => [
            'user_id' => ['required', 'exists:users,id'],
            'role_id' => ['required', 'exists:roles,id']
        ]
    ];

    /**
     * @var string[]
     */
    protected $fillable = ['name', 'description'];

    protected $casts = [
        'name' => RoleNameEnum::class // Todo: not scalable, but prevents Role API store method so leave for now
    ];

    protected $with = [
//        'permissions',
    ];

    const RoleWithDefaultPermissions = [
        'administrator' => [
            'can_manage_regions',
            'can_manage_partner_channels',
            'can_manage_external_enquiries',
            'can_manage_participants',
            'can_offer_place_to_events',
            'can_manage_users',
            'can_manage_invoices',
            'can_manage_sponsors',
            'can_manage_series',
            'can_manage_venues',
            'can_manage_cities',
            'can_manage_medals',
            'can_manage_roles',
            'can_manage_sites',
            'can_manage_enquiries',
            'can_manage_settings',
            'can_manage_permissions',
            'can_manage_partners',
            'can_manage_events',
            'can_manage_event_categories',
/*            'can_manage_pages',
            'can_manage_codes',
            'can_manage_charities',
            'can_manage_charity_categories',
            'can_view_timeline',
            'can_view_charity_checkout',
            'can_manage_charity_checkout',
            'can_manage_drip_emails',
            'can_manage_partner_listings',
            'can_manage_promotional_pages',
            'can_manage_corporates',
            'can_manage_newsletters',
            'can_manage_listings_pages',
            'can_manage_virtual_challenges',
            'can_manage_evidences',
            'can_manage_campaigns',
            'can_manage_partner_packages',
            'can_manage_account_managers',
            'can_manage_charity_enquiries',
            'can_manage_folders',
            'can_manage_custom_race_results',
            'can_manage_tutorials',
            'can_manage_market',
            'can_view_market',
        */        ],
/*        'account_manager' => [
            'can_manage_registrations',
            'can_manage_invoices',
            'can_manage_enquiries',
            'can_manage_charities',
            'can_manage_charity_categories',
            'can_view_timeline',
            'can_view_charity_checkout',
            'can_manage_charity_checkout',
            'can_manage_promotional_pages',
            'can_manage_contracts',
        ],
        'charity' => [
            'can_manage_market',
            'can_manage_registrations',
            'can_manage_external_enquiries',
            'can_manage_participants',
            'can_offer_place_to_events',
            'can_manage_enquiries',
            'can_personalize_drip_emails',
            'can_manage_newsletters',
        ],
        'charity_user' => [
            'can_manage_market',
            'can_manage_registrations',
            'can_manage_external_enquiries',
            'can_manage_participants',
            'can_offer_place_to_events',
            'can_manage_enquiries',
            'can_personalize_drip_emails',
            'can_manage_newsletters',
        ],
*/        'developer' => [
            'can_manage_regions',
            'can_manage_partner_channels',
            'can_manage_external_enquiries',
            'can_manage_participants',
            'can_offer_place_to_events',
            'can_manage_users',
            'can_manage_invoices',
            'can_manage_sponsors',
            'can_manage_series',
            'can_manage_venues',
            'can_manage_cities',
            'can_manage_medals',
            'can_manage_roles',
            'can_manage_sites',
            'can_manage_enquiries',
            'can_manage_settings',
            'can_manage_permissions',
            'can_manage_partners',
            'can_manage_events',
            'can_manage_event_categories',
/*            'can_manage_codes',
            'can_manage_charities',
            'can_manage_charity_categories',
            'can_manage_pages',
            'can_view_timeline',
            'can_view_charity_checkout',
            'can_manage_charity_checkout',
            'can_manage_drip_emails',
            'can_manage_partner_listings',
            'can_manage_promotional_pages',
            'can_manage_corporates',
            'can_manage_newsletters',
            'can_manage_listings_pages',
            'can_manage_virtual_challenges',
            'can_manage_evidences',
            'can_manage_campaigns',
            // 'can_manage_account_managers',
            // 'can_manage_folders',
            'can_manage_emails',
            'can_manage_market',
            'can_view_market',
*/        ],
        'event_manager' => [
            'can_manage_registrations',
            'can_manage_external_enquiries',
            'can_manage_participants',
            'can_manage_enquiries',
            'can_manage_events',
/*            'can_manage_campaigns',
            'can_manage_race_files',
            'can_manage_race_results',*/
        ],
/*
        'corporate' => [
            'can_manage_registrations',
            'can_manage_external_enquiries',
            'can_manage_participants',
            'can_manage_enquiries',
        ],
        'partner' => [
            'can_manage_registrations',
        ],*/
        'participant' => [
            'can_manage_registrations',
            'can_create_charity_checkout',
            'can_manage_challenges',
            'can_manage_custom_race_results',
        ],
/*        'content_manager' => [
            'can_manage_registrations',
            'can_manage_settings',
            'can_manage_partners',
            'can_manage_pages',
            'can_manage_events',
            'can_manage_charities',
            'can_manage_charity_categories',
            'can_manage_partner_listings',
            'can_manage_promotional_pages',
            'can_manage_newsletters',
            'can_manage_listings_pages',
            'can_manage_partner_packages',
            'can_manage_folders',
            'can_manage_tutorials',
        ],
        'runthrough_data' => [
            'can_manage_registrations',
            'can_manage_external_enquiries',
            'can_manage_participants',
            'can_manage_enquiries',
        ]*/
    ];

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    /**
     * Get the participant actions associated with the role
     *
     * @return HasMany
     */
    public function participantActions(): HasMany
    {
        return $this->hasMany(ParticipantAction::class);
    }

    /**
     * @return mixed
     */
    public function permits(): mixed
    {
        return $this->permissions->pluck('name')->unique();
    }

    /**
     * @param string $permission
     * @return void
     */
    public function allowTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::whereName($permission)->firstOrFail();
        }

        if (is_int($permission)) {
            $permission = Permission::findOrFail($permission);
        }

        $this->permissions()->syncWithoutDetaching($permission);
    }

    /**
     * @return void
     */
    public static function createDefaults()
    {
        foreach (self::ROLES as $role) {
            self::firstOrCreate([
                'name' => $role
            ]);
        }
    }

    /**
     * @param int $permissionId
     * @return void
     */
    public function grantPermission(int $permissionId)
    {
        $permission = Permission::findOrFail($permissionId);

        $this->permissions()->attach($permission->id);
    }

    /**
     * @param int $permissionId
     * @return void
     */
    public function revokePermission(int $permissionId)
    {
        $permission = Permission::findOrFail($permissionId);

        $this->permissions()->detach($permission->id);
    }

    /**
     * Check if the role has a permission
     *
     * @param int|string $permission
     * @return boolean
     */
    public function hasPermission(int|string $permission): bool
    {
        return $this->permits()->contains($permission);
    }

    /**
     * Scope a query to only include active users.
     *
     * @param Builder $query
     * @param int|null $siteId
     * @return Builder
     */
    public function scopeSiteOnly(Builder $query, int $siteId = null): Builder
    {
        return $query->where('site_id', $siteId ?? clientSiteId());
    }
}
