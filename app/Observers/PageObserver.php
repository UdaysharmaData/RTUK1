<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\GlobalSearchDataService;
use App\Services\DataServices\PageDataService;


class PageObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Page "created" event.
     *
     * @param Page $page
     * @return void
     */
    public function created(Page $page): void
    {
        CacheDataManager::flushAllCachedServiceListings(new PageDataService());
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Page "updated" event.
     *
     * @param Page $page
     * @return void
     */
    public function updated(Page $page): void
    {
//        CacheDataManager::flushAllCachedServiceListings(new PageDataService());
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Page "deleted" event.
     *
     * @param Page $page
     * @return void
     */
    public function deleted(Page $page): void
    {
        $page->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new PageDataService());
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Page "restored" event.
     * @param Page $page
     */
    public function restored(Page $page): void
    {
        CacheDataManager::flushAllCachedServiceListings(new PageDataService());
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }

    /**
     * Handle the Page "forceDeleted" event.
     * @param Page $page
     */
    public function forceDeleted(Page $page): void
    {
        $page->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new PageDataService());
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
    }
}
