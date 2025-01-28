<?php

namespace App\Observers;

use App\Models\Venue;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\GlobalSearchDataService;
use App\Services\DataServices\VenueDataService;


class VenueObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Venue "created" event.
     *
     * @param  \App\Models\Venue  $venue
     * @return void
     */
    public function created(Venue $venue)
    {
        CacheDataManager::flushAllCachedServiceListings(new VenueDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Venue "updated" event.
     *
     * @param  \App\Models\Venue  $venue
     * @return void
     */
    public function updated(Venue $venue)
    {
        CacheDataManager::flushAllCachedServiceListings(new VenueDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Venue "deleted" event.
     *
     * @param  \App\Models\Venue  $venue
     * @return void
     */
    public function deleted(Venue $venue)
    {
        $venue->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new VenueDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Venue "restored" event.
     *
     * @param  \App\Models\Venue  $venue
     * @return void
     */
    public function restored(Venue $venue)
    {
        CacheDataManager::flushAllCachedServiceListings(new VenueDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Venue "force deleted" event.
     *
     * @param  \App\Models\Venue  $venue
     * @return void
     */
    public function forceDeleted(Venue $venue)
    {
        $venue->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new VenueDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }
}
