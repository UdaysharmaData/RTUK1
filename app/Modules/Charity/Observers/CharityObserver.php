<?php

namespace App\Modules\Charity\Observers;

use App\Modules\Charity\Models\Charity;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\GlobalSearchDataService;

class CharityObserver
{
    /**
     * Handle charitys after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Charity "created" charity.
     *
     * @param  Charity  $charity
     * @return void
     */
    public function created(Charity $charity)
    {
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Charity "updated" charity.
     *
     * @param  Charity  $charity
     * @return void
     */
    public function updated(Charity $charity)
    {
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Charity "deleted" charity.
     *
     * @param  Charity  $charity
     * @return void
     */
    public function deleted(Charity $charity)
    {
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Charity "restored" charity.
     *
     * @param  Charity  $charity
     * @return void
     */
    public function restored(Charity $charity)
    {
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Charity "force deleted" charity.
     *
     * @param  Charity  $charity
     * @return void
     */
    public function forceDeleted(Charity $charity)
    {
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }
}
