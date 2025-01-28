<?php

namespace App\Modules\User\Models\Relations;

use App\Models\Faq;
use App\Modules\Setting\Models\Site;
use App\Modules\User\Models\Profile;
use App\Modules\User\Models\Contract;
use App\Modules\User\Models\SiteUser;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\Partner;
use App\Modules\Charity\Models\Campaign;
use App\Modules\User\Models\CharityUser;
use App\Modules\Event\Models\EventManager;
use App\Modules\User\Models\VerificationCode;
use App\Modules\Participant\Models\Participant;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Event\Models\EventManagerDownload;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use App\Modules\Participant\Models\ParticipantAction;
use App\Modules\User\Models\ParticipantProfile;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait UserRelations
{
    /**
     * @return HasOne
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * @return HasMany
     */
    public function verificationCodes(): HasMany
    {
        return $this->hasMany(VerificationCode::class);
    }

    // /**
    //  * Get the user's charities (account managers).
    //  *
    //  * @return Illuminate\Database\Eloquent\Relations\Relation
    //  */
    // public function charities()
    // {
    //     return $this->hasMany('App\Models\Charity', 'manager_id', 'id');
    // }

    public function corporate()
    {
        return $this->hasOne('App\Modules\Corporate\Models\Corporate');
    }

    /**
     * Get the contracts that belongs to the user(account_manager).
     * Account managers have written contracts which they upload/save under the Contract model. The charities they manage are bound to these contracts.
     *
     * @return HasMany
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get the events associated with the user.
     * The events to which the user has participated.
     *
     * @return HasMany
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function raceResults()
    {
        return $this->hasMany('App\Models\RaceResult');
    }

    public function rankingsProfile()
    {
        return $this->hasOne('App\Models\RankingsProfile');
    }

    public function emailPreference()
    {
        return $this->hasOne('App\Models\EmailPreference');
    }

    /**
     * Get the charities managed by the user. These are charities having the manager type relationship with the user.
     * The relationship between the user and its charities is supposed to be a many-to-many relationship (as per the schema - for future use if requested) but since the application currently permits account managers to manage many charities, we shall use this charities relationship and make use of the relationships below too (for now) to get specific data.
     * charities   - Get the charities managed by the account manager or charity_user. Only users having the whole account manager or charity_user should access this method (for easy querying)
     * charityUser - Get the charity (CharityUser) having an association of type (owner, participant) with the user. Only users having the roles charity, and participant should access this method (for easy querying)
     * Only users having the whole account manager or charity_user should access this method.
     *
     * @return BelongsToMany
     */
    public function charities(): BelongsToMany
    {
        return $this->belongsToMany(Charity::class, 'charity_user', 'user_id', 'charity_id')->using(CharityUser::class)->withPivot('id', 'ref', 'type')->withTimeStamps();
    }

    /**
     * Get the charity having the owner, user and participant type relationship with the user. NB: A user can have the user type relationship with only one charity
     * Only access this method for users having the owner, user, and participant type relationship with the charity
     *
     * @return HasOne
     */
    public function charityUser(): HasOne
    {
        return $this->hasOne(CharityUser::class)->latestOfMany();
    }

    /**
     * Get the event manager (for users of role event_manager).
     *
     * @return HasOne
     */
    public function eventManager(): HasOne
    {
        return $this->hasOne(EventManager::class);
    }

    /**
     * Get the campaigns that belongs to the user(account_manager).
     *
     * @return HasMany
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Get the partner associated with the user
     *
     * @return HasOne
     */
    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
    }

    /**
     * Get the user's (event_manager) downloads.
     *
     * @return HasMany
     */
    public function eventManagerDownloads(): HasMany
    {
        return $this->hasMany(EventManagerDownload::class);
    }

    /**
     * Get the user's (event_manager) latest download.
     *
     * @return HasOne
     */
    public function latestEventManagerDownload(): HasOne
    {
        return $this->hasOne(EventManagerDownload::class)->latestOfMany();
    }

    /**
     * Get the sites associated with the user (having the administrator role).
     *
     * @return BelongsToMany
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class)
            ->using(SiteUser::class)
            ->withPivot(['id', 'ref', 'status'])
            ->withTimestamps();
    }

    /**
     * Get the external enquiries associated with the user
     *
     * @return HasMany
     */
    public function externalEnquiries(): HasMany
    {
        return $this->hasMany(ExternalEnquiry::class, 'email', 'email');
    }

    /**
     * Get the actions made by the user on the participants records
     *
     * @return HasMany
     */
    public function participantActions(): HasMany
    {
        return $this->hasMany(ParticipantAction::class);
    }

    /**
     * searchHistories
     *
     * @return HasMany
     */
    public function searchHistories(): HasMany
    {
        return $this->hasMany('App\Models\SearchHistory')->where('site_id', clientSiteId());
    }

    /**
     * Get the participant profile associated with the user.
     *
     * @return void
     */
    public function participantProfile()
    {
        return $this->hasOneThrough(
            ParticipantProfile::class,
            Profile::class,
            'user_id', // Foreign key on the profiles table
            'profile_id', // Foreign key on the participant_profiles table
            'id', // Local key on the users table
            'id' // Local key on the profiles table
        );
    }
}
