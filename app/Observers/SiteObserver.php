<?php

namespace App\Observers;

use App\Enums\CacheTypeEnum;
use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\Cache;
use App\Services\ApiClient\ApiClientSettings;

class SiteObserver
{
    /**
     * Handle the Site "created" event.
     *
     * @param \App\Models\Site $site
     * @return void
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function created(Site $site)
    {
        ApiClientSettings::refreshCache(CacheTypeEnum::Site);
    
        Cache::put('sites', Site::all(), now()->addHour());
    }

    /**
     * Handle the Site "updated" event.
     *
     * @param \App\Models\Site $site
     * @return void
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function updated(Site $site)
    {
        ApiClientSettings::refreshCache(CacheTypeEnum::Site);

        Cache::put('sites', Site::all(), now()->addHour());
    }

    /**
     * Handle the Site "deleted" event.
     *
     * @param \App\Models\Site $site
     * @return void
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function deleted(Site $site)
    {
        ApiClientSettings::refreshCache(CacheTypeEnum::Site);

        Cache::put('sites', Site::all(), now()->addHour());
    }

    /**
     * Handle the Site "restored" event.
     *
     * @param \App\Models\Site $site
     * @return void
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function restored(Site $site)
    {
        ApiClientSettings::refreshCache(CacheTypeEnum::Site);
    
        Cache::put('sites', Site::all(), now()->addHour());
    }

    /**
     * Handle the Site "force deleted" event.
     *
     * @param \App\Models\Site $site
     * @return void
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function forceDeleted(Site $site)
    {
        ApiClientSettings::refreshCache(CacheTypeEnum::Site);
    
        Cache::put('sites', Site::all(), now()->addHour());
    }
}
