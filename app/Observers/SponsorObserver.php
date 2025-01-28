<?php

namespace App\Observers;

use App\Modules\Event\Models\Sponsor;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\SponsorDataService;

class SponsorObserver
{
    
   /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Sponsor "created" event.
     *
     * @param  \App\Models\Sponsor  $sponsor
     * @return void
     */
    public function created(Sponsor $sponsor)
    {
        CacheDataManager::flushAllCachedServiceListings(new SponsorDataService);
    }

    /**
     * Handle the Sponsor "updated" event.
     *
     * @param  \App\Models\Sponsor  $sponsor
     * @return void
     */
    public function updated(Sponsor $sponsor)
    {
        CacheDataManager::flushAllCachedServiceListings(new SponsorDataService);
    }

    /**
     * Handle the Sponsor "deleted" event.
     *
     * @param  \App\Models\Sponsor  $sponsor
     * @return void
     */
    public function deleted(Sponsor $sponsor)
    {
        CacheDataManager::flushAllCachedServiceListings(new SponsorDataService);
    }

    /**
     * Handle the Sponsor "restored" event.
     *
     * @param  \App\Models\Sponsor  $sponsor
     * @return void
     */
    public function restored(Sponsor $sponsor)
    {
        CacheDataManager::flushAllCachedServiceListings(new SponsorDataService);
    }

    /**
     * Handle the Sponsor "force deleted" event.
     *
     * @param  \App\Models\Sponsor  $sponsor
     * @return void
     */
    public function forceDeleted(Sponsor $sponsor)
    {
        CacheDataManager::flushAllCachedServiceListings(new SponsorDataService);
    }
}
