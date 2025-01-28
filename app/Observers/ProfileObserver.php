<?php

namespace App\Observers;

use App\Modules\User\Models\Profile;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;

class ProfileObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

//    /**
//     * Handle the Profile "created" event.
//     *
//     * @param Profile $profile
//     * @return void
//     */
//    public function created(Profile $profile): void
//    {
//
//    }

    /**
     * Handle the Profile "updated" event.
     *
     * @param Profile $profile
     * @return void
     */
    public function updated(Profile $profile): void
    {
        CacheDataManager::flushAllCachedServiceListings(new UserDataService);
    }

    /**
     * Handle the Profile "deleted" event.
     *
     * @param Profile $profile
     * @return void
     */
    public function deleted(Profile $profile): void
    {
        CacheDataManager::flushAllCachedServiceListings(new UserDataService);
    }

    /**
     * Handle the Profile "restored" event.
     * @param Profile $profile
     */
    public function restored(Profile $profile): void
    {
        CacheDataManager::flushAllCachedServiceListings(new UserDataService);
    }

    /**
     * Handle the Profile "forceDeleted" event.
     * @param Profile $profile
     */
    public function forceDeleted(Profile $profile): void
    {
        CacheDataManager::flushAllCachedServiceListings(new UserDataService);
    }
}
