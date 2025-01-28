<?php

namespace App\Observers;

use App\Modules\User\Models\Role;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\RoleDataService;

class RoleObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Role "created" event.
     *
     * @param Role $role
     * @return void
     * @throws \Exception
     */
    public function created(Role $role): void
    {
        CacheDataManager::flushAllCachedServiceListings(new RoleDataService);
    }

    /**
     * Handle the Role "updated" event.
     *
     * @param Role $role
     * @return void
     * @throws \Exception
     */
    public function updated(Role $role): void
    {
        CacheDataManager::flushAllCachedServiceListings(new RoleDataService);
    }

    /**
     * Handle the Role "deleted" event.
     *
     * @param Role $role
     * @return void
     * @throws \Exception
     */
    public function deleted(Role $role): void
    {
        CacheDataManager::flushAllCachedServiceListings(new RoleDataService);
    }

    /**
     * Handle the Role "restored" event.
     * @param Role $role
     */
    public function restored(Role $role): void
    {
        CacheDataManager::flushAllCachedServiceListings(new RoleDataService);
    }

    /**
     * Handle the Role "forceDeleted" event.
     * @param Role $role
     */
    public function forceDeleted(Role $role): void
    {
        CacheDataManager::flushAllCachedServiceListings(new RoleDataService);
    }
}
