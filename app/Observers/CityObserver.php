<?php

namespace App\Observers;

use App\Models\City;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\CityDataService;
use App\Services\DataServices\GlobalSearchDataService;

class CityObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the City "created" event.
     *
     * @param  \App\Models\City  $city
     * @return void
     */
    public function created(City $city)
    {
        CacheDataManager::flushAllCachedServiceListings(new CityDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the City "updated" event.
     *
     * @param  \App\Models\City  $city
     * @return void
     */
    public function updated(City $city)
    {
        CacheDataManager::flushAllCachedServiceListings(new CityDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the City "deleted" event.
     *
     * @param  \App\Models\City  $city
     * @return void
     */
    public function deleted(City $city)
    {
        $city->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new CityDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the City "restored" event.
     *
     * @param  \App\Models\City  $city
     * @return void
     */
    public function restored(City $city)
    {
        CacheDataManager::flushAllCachedServiceListings(new CityDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the City "force deleted" event.
     *
     * @param  \App\Models\City  $city
     * @return void
     */
    public function forceDeleted(City $city)
    {
        $city->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new CityDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }
}
