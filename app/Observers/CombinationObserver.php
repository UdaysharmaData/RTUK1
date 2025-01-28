<?php

namespace App\Observers;

use App\Models\Combination;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\CombinationDataService;
use App\Services\DataServices\EventClientDataService;
use App\Services\DataServices\GlobalSearchDataService;

class CombinationObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Combination "created" event.
     *
     * @param Combination $combination
     * @return void
     */
    public function created(Combination $combination): void
    {
        CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Combination "updated" event.
     *
     * @param Combination $combination
     * @return void
     */
    public function updated(Combination $combination): void
    {
        CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
    }

    /**
     * Handle the Combination "deleted" event.
     *
     * @param Combination $combination
     * @return void
     */
    public function deleted(Combination $combination): void
    {
        $combination->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Combination "restored" event.
     * @param Combination $combination
     */
    public function restored(Combination $combination): void
    {
        CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Combination "forceDeleted" event.
     * @param Combination $combination
     */
    public function forceDeleted(Combination $combination): void
    {
        $combination->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new CombinationDataService());
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }
}
