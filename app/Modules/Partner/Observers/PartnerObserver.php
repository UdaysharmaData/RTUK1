<?php

namespace App\Modules\Partner\Observers;

use App\Modules\Partner\Models\Partner;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\PartnerDataService;

class PartnerObserver
{
    /**
     * Handle partners after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Partner "created" Partner.
     *
     * @param  Partner  $partner
     * @return void
     */
    public function created(Partner $partner)
    {
        CacheDataManager::flushAllCachedServiceListings(new PartnerDataService);
    }

    /**
     * Handle the Partner "updated" Partner.
     *
     * @param  Partner  $partner
     * @return void
     */
    public function updated(Partner $partner)
    {
        CacheDataManager::flushAllCachedServiceListings(new PartnerDataService);
    }

    /**
     * Handle the Partner "deleted" Partner.
     *
     * @param  Partner  $partner
     * @return void
     */
    public function deleted(Partner $partner)
    {
        CacheDataManager::flushAllCachedServiceListings(new PartnerDataService);
    }

    /**
     * Handle the Partner "restored" Partner.
     *
     * @param  Partner  $partner
     * @return void
     */
    public function restored(Partner $partner)
    {
        CacheDataManager::flushAllCachedServiceListings(new PartnerDataService);
    }

    /**
     * Handle the Partner "force deleted" Partner.
     *
     * @param  Partner  $partner
     * @return void
     */
    public function forceDeleted(Partner $partner)
    {
        CacheDataManager::flushAllCachedServiceListings(new PartnerDataService);
    }
}
