<?php

namespace App\Observers;

use App\Models\Medal;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\MedalDataService;
use Illuminate\Support\Facades\Log;

class MedalObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Medal "created" event.
     *
     * @param  \App\Models\Medal  $medal
     * @return void
     */
    public function created(Medal $medal)
    {
        CacheDataManager::flushAllCachedServiceListings(new MedalDataService);
    }

    /**
     * Handle the Medal "updated" event.
     *
     * @param  \App\Models\Medal  $medal
     * @return void
     */
    public function updated(Medal $medal)
    {
        CacheDataManager::flushAllCachedServiceListings(new MedalDataService);
    }

    /**
     * Handle the Medal "deleted" event.
     *
     * @param  \App\Models\Medal  $medal
     * @return void
     */
    public function deleted(Medal $medal)
    {
        CacheDataManager::flushAllCachedServiceListings(new MedalDataService);
    }

    /**
     * Handle the Medal "restored" event.
     *
     * @param  \App\Models\Medal  $medal
     * @return void
     */
    public function restored(Medal $medal)
    {
        CacheDataManager::flushAllCachedServiceListings(new MedalDataService);
    }

    /**
     * Handle the Medal "force deleted" event.
     *
     * @param  \App\Models\Medal  $medal
     * @return void
     */
    public function forceDeleted(Medal $medal)
    {
        CacheDataManager::flushAllCachedServiceListings(new MedalDataService);
    }
}
