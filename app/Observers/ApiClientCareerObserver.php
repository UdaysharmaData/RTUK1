<?php

namespace App\Observers;

use App\Models\ApiClientCareer;

class ApiClientCareerObserver
{
    /**
     * Handle the ApiClientCareer "created" event.
     *
     * @param  \App\Models\ApiClientCareer  $apiClientCareer
     * @return void
     */
    public function created(ApiClientCareer $apiClientCareer)
    {
        ApiClientCareer::refreshCachedCareersCollection();
    }

    /**
     * Handle the ApiClientCareer "updated" event.
     *
     * @param  \App\Models\ApiClientCareer  $apiClientCareer
     * @return void
     */
    public function updated(ApiClientCareer $apiClientCareer)
    {
        ApiClientCareer::refreshCachedCareersCollection();
    }

    /**
     * Handle the ApiClientCareer "deleted" event.
     *
     * @param  \App\Models\ApiClientCareer  $apiClientCareer
     * @return void
     */
    public function deleted(ApiClientCareer $apiClientCareer)
    {
        ApiClientCareer::refreshCachedCareersCollection();
    }

    /**
     * Handle the ApiClientCareer "restored" event.
     *
     * @param  \App\Models\ApiClientCareer  $apiClientCareer
     * @return void
     */
    public function restored(ApiClientCareer $apiClientCareer)
    {
        ApiClientCareer::refreshCachedCareersCollection();
    }

    /**
     * Handle the ApiClientCareer "force deleted" event.
     *
     * @param  \App\Models\ApiClientCareer  $apiClientCareer
     * @return void
     */
    public function forceDeleted(ApiClientCareer $apiClientCareer)
    {
        ApiClientCareer::refreshCachedCareersCollection();
    }
}
