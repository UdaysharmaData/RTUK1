<?php

namespace App\Modules\Enquiry\Observers;

use App\Modules\Enquiry\Models\Enquiry;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EnquiryDataService;

class EnquiryObserver
{
    /**
     * Handle the Enquiry "created" event.
     *
     * @param  Enquiry  $enquiry
     * @return void
     */
    public function created(Enquiry $enquiry)
    {
        CacheDataManager::flushAllCachedServiceListings(new EnquiryDataService);
    }

    /**
     * Handle the Enquiry "updated" event.
     *
     * @param  Enquiry  $enquiry
     * @return void
     */
    public function updated(Enquiry $enquiry)
    {
        CacheDataManager::flushAllCachedServiceListings(new EnquiryDataService);
    }

    /**
     * Handle the Enquiry "deleted" event.
     *
     * @param  Enquiry  $enquiry
     * @return void
     */
    public function deleted(Enquiry $enquiry)
    {
        CacheDataManager::flushAllCachedServiceListings(new EnquiryDataService);
    }

    /**
     * Handle the Enquiry "restored" event.
     *
     * @param  Enquiry  $enquiry
     * @return void
     */
    public function restored(Enquiry $enquiry)
    {
        CacheDataManager::flushAllCachedServiceListings(new EnquiryDataService);
    }

    /**
     * Handle the Enquiry "force deleted" event.
     *
     * @param  Enquiry  $enquiry
     * @return void
     */
    public function forceDeleted(Enquiry $enquiry)
    {
        CacheDataManager::flushAllCachedServiceListings(new EnquiryDataService);
    }
}
