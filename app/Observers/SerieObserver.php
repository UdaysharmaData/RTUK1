<?php

namespace App\Observers;

use App\Modules\Event\Models\Serie;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\SerieDataService;

class SerieObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Serie "created" event.
     *
     * @param  \App\Models\Serie  $serie
     * @return void
     */
    public function created(Serie $serie)
    {
        CacheDataManager::flushAllCachedServiceListings(new SerieDataService);
    }

    /**
     * Handle the Serie "updated" event.
     *
     * @param  \App\Models\Serie  $serie
     * @return void
     */
    public function updated(Serie $serie)
    {
        CacheDataManager::flushAllCachedServiceListings(new SerieDataService);
    }

    /**
     * Handle the Serie "deleted" event.
     *
     * @param  \App\Models\Serie  $serie
     * @return void
     */
    public function deleted(Serie $serie)
    {
        CacheDataManager::flushAllCachedServiceListings(new SerieDataService);
    }

    /**
     * Handle the Serie "restored" event.
     *
     * @param  \App\Models\Serie  $serie
     * @return void
     */
    public function restored(Serie $serie)
    {
        CacheDataManager::flushAllCachedServiceListings(new SerieDataService);
    }

    /**
     * Handle the Serie "force deleted" event.
     *
     * @param  \App\Models\Serie  $serie
     * @return void
     */
    public function forceDeleted(Serie $serie)
    {
        CacheDataManager::flushAllCachedServiceListings(new SerieDataService);
    }
}
