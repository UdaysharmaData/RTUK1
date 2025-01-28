<?php

namespace App\Observers;

use App\Enums\RoleNameEnum;
use App\Modules\User\Models\User;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;

class UserObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the User "created" event.
     *
     * @param User $user
     * @return void
     * @throws \Exception
     */
    public function created(User $user): void
    {
        $user->bootstrapUserRelatedProperties();

        CacheDataManager::flushAllCachedServiceListings(new UserDataService);
    }

    /**
     * Handle the User "updated" event.
     *
     * @param User $user
     * @return void
     * @throws \Exception
     */
    public function updated(User $user): void
    {
        CacheDataManager::flushAllCachedServiceListings(new UserDataService);
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param User $user
     * @return void
     * @throws \Exception
     */
    public function deleted(User $user): void
    {
        CacheDataManager::flushAllCachedServiceListings(new UserDataService);
    }

    /**
     * Handle the User "restored" event.
     * @param User $user
     */
    public function restored(User $user): void
    {
        CacheDataManager::flushAllCachedServiceListings(new UserDataService);
    }

    /**
     * Handle the User "forceDeleted" event.
     * @param User $user
     */
    public function forceDeleted(User $user): void
    {
        CacheDataManager::flushAllCachedServiceListings(new UserDataService);
    }
}
