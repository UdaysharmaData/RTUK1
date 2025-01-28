<?php

namespace App\Modules\Event\Observers;

use App\Modules\Event\Models\Event;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\CityDataService;
use App\Services\DataServices\EventDataService;
use App\Services\DataServices\VenueDataService;
use App\Services\DataServices\EventClientDataService;
use App\Services\DataServices\GlobalSearchDataService;
use App\Services\DataServices\PartnerEventDataService;
use App\Services\DataServices\EventCategoryDataService;

class EventObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Event "created" event.
     *
     * @param  Event  $event
     * @return void
     */
    public function created(Event $event)
    {
        CacheDataManager::flushAllCachedServiceListings(new EventDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        CacheDataManager::flushAllCachedServiceListings(new CityDataService);
        CacheDataManager::flushAllCachedServiceListings(new VenueDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventCategoryDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
    }

    /**
     * Handle the Event "updated" event.
     *
     * @param  Event  $event
     * @return void
     */
    public function updated(Event $event)
    {
        CacheDataManager::flushAllCachedServiceListings(new EventDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        CacheDataManager::flushAllCachedServiceListings(new CityDataService);
        CacheDataManager::flushAllCachedServiceListings(new VenueDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventCategoryDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
    }

    /**
     * Handle the Event "deleted" event.
     *
     * @param  Event  $event
     * @return void
     */
    public function deleted(Event $event)
    {
        $event->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new EventDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        CacheDataManager::flushAllCachedServiceListings(new CityDataService);
        CacheDataManager::flushAllCachedServiceListings(new VenueDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventCategoryDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
    }

    /**
     * Handle the Event "restored" event.
     *
     * @param  Event  $event
     * @return void
     */
    public function restored(Event $event)
    {
        CacheDataManager::flushAllCachedServiceListings(new EventDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        CacheDataManager::flushAllCachedServiceListings(new CityDataService);
        CacheDataManager::flushAllCachedServiceListings(new VenueDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventCategoryDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
    }

    /**
     * Handle the Event "force deleted" event.
     *
     * @param  Event  $event
     * @return void
     */
    public function forceDeleted(Event $event)
    {
        $event->addDefaultRedirect();
        CacheDataManager::flushAllCachedServiceListings(new EventDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
        CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
        CacheDataManager::flushAllCachedServiceListings(new CityDataService);
        CacheDataManager::flushAllCachedServiceListings(new VenueDataService);
        CacheDataManager::flushAllCachedServiceListings(new EventCategoryDataService);
        (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
    }
}
