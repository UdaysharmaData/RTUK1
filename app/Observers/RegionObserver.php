<?php

namespace App\Observers;

use App\Models\Region;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\GlobalSearchDataService;
use App\Services\DataServices\RegionDataService;


class RegionObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Region "created" event.
     *
     * @param  \App\Models\Region  $region
     * @return void
     */
    public function created(Region $region)
    {
        CacheDataManager::flushAllCachedServiceListings(new RegionDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Region "updated" event.
     *
     * @param  \App\Models\Region  $region
     * @return void
     */
    public function updated(Region $region)
    {
        CacheDataManager::flushAllCachedServiceListings(new RegionDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Region "deleted" event.
     *
     * @param  \App\Models\Region  $region
     * @return void
     */
    public function deleted(Region $region)
    {
        $region->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new RegionDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Region "restored" event.
     *
     * @param  \App\Models\Region  $region
     * @return void
     */
    public function restored(Region $region)
    {
        CacheDataManager::flushAllCachedServiceListings(new RegionDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Region "force deleted" event.
     *
     * @param  \App\Models\Region  $region
     * @return void
     */
    public function forceDeleted(Region $region)
    {
        $region->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new RegionDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }
}
