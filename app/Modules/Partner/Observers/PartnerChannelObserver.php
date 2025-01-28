<?php

namespace App\Modules\Partner\Observers;

use App\Modules\Partner\Models\PartnerChannel;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\PartnerDataService;
use App\Services\DataServices\PartnerChannelDataService;

class PartnerChannelObserver
{
     /**
     * Handle partners after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the PartnerChannel "created" event.
     *
     * @param  \App\Models\PartnerChannel  $partnerChannel
     * @return void
     */
    public function created(PartnerChannel $partnerChannel)
    {
        CacheDataManager::flushAllCachedServiceListings(new PartnerChannelDataService);
        CacheDataManager::flushAllCachedServiceListings(new PartnerDataService);
    }

    /**
     * Handle the PartnerChannel "updated" event.
     *
     * @param  \App\Models\PartnerChannel  $partnerChannel
     * @return void
     */
    public function updated(PartnerChannel $partnerChannel)
    {
        CacheDataManager::flushAllCachedServiceListings(new PartnerChannelDataService);
        CacheDataManager::flushAllCachedServiceListings(new PartnerDataService);
    }

    /**
     * Handle the PartnerChannel "deleted" event.
     *
     * @param  \App\Models\PartnerChannel  $partnerChannel
     * @return void
     */
    public function deleted(PartnerChannel $partnerChannel)
    {
        CacheDataManager::flushAllCachedServiceListings(new PartnerChannelDataService);
        CacheDataManager::flushAllCachedServiceListings(new PartnerDataService);
    }
}
