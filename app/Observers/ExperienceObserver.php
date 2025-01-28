<?php

namespace App\Observers;

use App\Models\Experience;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EventClientDataService;
use App\Services\DataServices\ExperienceDataService;

class ExperienceObserver
{
   /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Experience "created" event.
     *
     * @param  Experience  $experience
     * @return void
     */
    public function created(Experience $experience)
    {
        CacheDataManager::flushAllCachedServiceListings(new ExperienceDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
    }

    /**
     * Handle the Experience "updated" event.
     *
     * @param  Experience  $experience
     * @return void
     */
    public function updated(Experience $experience)
    {
        CacheDataManager::flushAllCachedServiceListings(new ExperienceDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
    }

    /**
     * Handle the Experience "deleted" event.
     *
     * @param  Experience  $experience
     * @return void
     */
    public function deleted(Experience $experience)
    {
        CacheDataManager::flushAllCachedServiceListings(new ExperienceDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
    }

    /**
     * Handle the Experience "restored" event.
     *
     * @param  Experience  $experience
     * @return void
     */
    public function restored(Experience $experience)
    {
        CacheDataManager::flushAllCachedServiceListings(new ExperienceDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
    }

    /**
     * Handle the Experience "force deleted" event.
     *
     * @param  Experience  $experience
     * @return void
     */
    public function forceDeleted(Experience $experience)
    {
        CacheDataManager::flushAllCachedServiceListings(new ExperienceDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
    }
}
