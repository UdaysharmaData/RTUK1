<?php

namespace App\Modules\Enquiry\Observers;

use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\ExternalEnquiryDataService;

class ExternalEnquiryObserver
{
    /**
     * Handle the Enquiry "created" event.
     *
     * @param  ExternalEnquiry  $enquiry
     * @return void
     */
    public function created(ExternalEnquiry $enquiry)
    {
        CacheDataManager::flushAllCachedServiceListings(new ExternalEnquiryDataService);
    }

    /**
     * Handle the Enquiry "updated" event.
     *
     * @param  ExternalEnquiry  $enquiry
     * @return void
     */
    public function updated(ExternalEnquiry $enquiry)
    {
        CacheDataManager::flushAllCachedServiceListings(new ExternalEnquiryDataService);
    }

    /**
     * Handle the Enquiry "deleted" event.
     *
     * @param  ExternalEnquiry  $enquiry
     * @return void
     */
    public function deleted(ExternalEnquiry $enquiry)
    {
        CacheDataManager::flushAllCachedServiceListings(new ExternalEnquiryDataService);
    }

    /**
     * Handle the Enquiry "restored" event.
     *
     * @param  ExternalEnquiry  $enquiry
     * @return void
     */
    public function restored(ExternalEnquiry $enquiry)
    {
        CacheDataManager::flushAllCachedServiceListings(new ExternalEnquiryDataService);
    }

    /**
     * Handle the Enquiry "force deleted" event.
     *
     * @param  ExternalEnquiry  $enquiry
     * @return void
     */
    public function forceDeleted(ExternalEnquiry $enquiry)
    {
        CacheDataManager::flushAllCachedServiceListings(new ExternalEnquiryDataService);
    }
}
