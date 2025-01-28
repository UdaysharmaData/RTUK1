<?php

namespace App\Observers;

use App\Models\Redirect;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\RedirectDataService;

class RedirectObserver
{
    /**
     * Handle the Redirect "created" event.
     *
     * @param  \App\Models\Redirect  $redirect
     * @return void
     */
    public function created(Redirect $redirect)
    {
        CacheDataManager::flushAllCachedServiceListings(new RedirectDataService());
    }

    /**
     * Handle the Redirect "updated" event.
     *
     * @param  \App\Models\Redirect  $redirect
     * @return void
     */
    public function updated(Redirect $redirect)
    {
        CacheDataManager::flushAllCachedServiceListings(new RedirectDataService());
    }

    /**
     * Handle the Redirect "deleted" event.
     *
     * @param  \App\Models\Redirect  $redirect
     * @return void
     */
    public function deleted(Redirect $redirect)
    {
        CacheDataManager::flushAllCachedServiceListings(new RedirectDataService());
    }

    /**
     * Handle the Redirect "restored" event.
     *
     * @param  \App\Models\Redirect  $redirect
     * @return void
     */
    public function restored(Redirect $redirect)
    {
        CacheDataManager::flushAllCachedServiceListings(new RedirectDataService());
    }

    /**
     * Handle the Redirect "force deleted" event.
     *
     * @param  \App\Models\Redirect  $redirect
     * @return void
     */
    public function forceDeleted(Redirect $redirect)
    {
        CacheDataManager::flushAllCachedServiceListings(new RedirectDataService());
    }
}
