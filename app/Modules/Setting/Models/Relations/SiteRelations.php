<?php

namespace App\Modules\Setting\Models\Relations;

use App\Models\Region;
use App\Models\Upload;
use App\Models\Sitemap;
use App\Models\ApiClient;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use App\Modules\User\Models\SiteUser;
use App\Modules\Setting\Models\Setting;
use App\Modules\Partner\Models\Partner;
use App\Modules\Charity\Models\Invoice;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\User\Models\Permission;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Setting\Models\Organisation;
use App\Modules\Setting\Models\SettingCustomField;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Setting\Models\Traits\HasManySitemaps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait SiteRelations
{
    use HasManySitemaps;

    // /**
    //  * Get the charity categories associated with the site.
    //  * @return HasMany
    //  */
    // public function charityCategories(): HasMany
    // {
    //     return $this->hasMany(CharityCategory::class);
    // }

    /**
     * Get the event categories that belong to the site.
     * @return HasMany
     */
    public function eventCategories(): HasMany
    {
        return $this->hasMany(EventCategory::class);
    }

    /**
     * Get the invoices associated with the site.
     * @return HasMany
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the uploads associated with the site.
     * @return HasMany
     */
    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }

    /**
     * Get the setting
     *
     * @return HasOne
     */
    public function setting(): HasOne
    {
        return $this->HasOne(Setting::class);
    }

    /**
     * Get the regions associated with the site
     *
     * @return HasMany
     */
    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    /**
     * Get the roles associated with the site.
     *
     */
    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Get the permissions associated with the site.
     *
     */
    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * associated API clients
     * @return HasMany
     */
    public function apiClients(): HasMany
    {
        return $this->hasMany(ApiClient::class);
    }

    /**
     * Get the users associated with the site (having the administrator role).
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(SiteUser::class)->withPivot('id', 'ref', 'status')->withTimestamps();
    }

    /**
     * Get the enquiries (website) associated with the site.
     *
     * @return HasMany
     */
    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    /**
     * Get the partners associated with the site.
     *
     * @return HasMany
     */
    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }
    
    /**
     * Get the setting custom fields associated with the site.
     *
     * @return void
     */
    public function settingCustomFields()
    {
        return $this->hasManyThrough(
            SettingCustomField::class,
            Setting::class,
            'site_id',
            'setting_id'
        );
    }

    /**
     * Get the organisation associated with the site.
     * 
     * @return BelongsTo
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }
}
