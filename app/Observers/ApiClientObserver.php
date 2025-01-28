<?php

namespace App\Observers;

use App\Enums\CacheTypeEnum;
use App\Models\ApiClient;
use App\Services\ApiClient\ApiClientSettings;

class ApiClientObserver
{
    /**
     * Handle the ApiClient "created" event.
     *
     * @param \App\Models\ApiClient $apiClient
     * @return void
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function created(ApiClient $apiClient)
    {
        ApiClientSettings::refreshCache(CacheTypeEnum::ApiClient);
    }

    /**
     * Handle the ApiClient "updated" event.
     *
     * @param \App\Models\ApiClient $apiClient
     * @return void
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function updated(ApiClient $apiClient)
    {
        ApiClientSettings::refreshCache(CacheTypeEnum::ApiClient);
    }

    /**
     * Handle the ApiClient "deleted" event.
     *
     * @param \App\Models\ApiClient $apiClient
     * @return void
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function deleted(ApiClient $apiClient)
    {
        ApiClientSettings::refreshCache(CacheTypeEnum::ApiClient);
    }

    /**
     * Handle the ApiClient "restored" event.
     *
     * @param \App\Models\ApiClient $apiClient
     * @return void
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function restored(ApiClient $apiClient)
    {
        ApiClientSettings::refreshCache(CacheTypeEnum::ApiClient);
    }

    /**
     * Handle the ApiClient "force deleted" event.
     *
     * @param \App\Models\ApiClient $apiClient
     * @return void
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function forceDeleted(ApiClient $apiClient)
    {
        ApiClientSettings::refreshCache(CacheTypeEnum::ApiClient);
    }
}
