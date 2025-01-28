<?php

namespace App\Modules\Event\Observers;

use App\Modules\Event\Models\EventCategory;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EventCategoryDataService;
use App\Services\DataServices\EventClientDataService;
use App\Services\DataServices\EventDataService;
use App\Services\DataServices\GlobalSearchDataService;
use App\Services\DataServices\PartnerEventDataService;

class EventCategoryObserver
{
    /**
     * Handle eventCategorys after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the EventCategory "created" eventCategory.
     *
     * @param  EventCategory  $eventCategory
     * @return void
     */
    public function created(EventCategory $eventCategory)
    {
        CacheDataManager::flushAllCachedServiceListings(new EventCategoryDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
    }

    /**
     * Handle the EventCategory "updated" eventCategory.
     *
     * @param  EventCategory  $eventCategory
     * @return void
     */
    public function updated(EventCategory $eventCategory)
    {
        CacheDataManager::flushAllCachedServiceListings(new EventCategoryDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
    }


    /**
     * Handle the EventCategory "deleted" eventCategory.
     *
     * @param  EventCategory  $eventCategory
     * @return void
     */
    public function deleted(EventCategory $eventCategory)
    {
        $eventCategory->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new EventCategoryDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
    }

    /**
     * Handle the EventCategory "restored" eventCategory.
     *
     * @param  EventCategory  $eventCategory
     * @return void
     */
    public function restored(EventCategory $eventCategory)
    {
        CacheDataManager::flushAllCachedServiceListings(new EventCategoryDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
    }

    /**
     * Handle the EventCategory "force deleted" eventCategory.
     *
     * @param  EventCategory  $eventCategory
     * @return void
     */
    public function forceDeleted(EventCategory $eventCategory)
    {
        $eventCategory->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new EventCategoryDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
    }
}
