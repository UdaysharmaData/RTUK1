<?php

namespace App\Observers;

use Carbon\Carbon;
use App\Models\Sitemap;

class SitemapObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Sitemap "creating" event.
     *
     * @param  \App\Models\Sitemap  $sitemap
     * @return void
     */
    public function creating(Sitemap $sitemap)
    {
        if (! $sitemap->latest_updated_at) {
            $sitemap->latest_updated_at = Carbon::now();
        }

        if (! $sitemap->oldest_updated_at) {
            $sitemap->oldest_updated_at = Carbon::now();
        }
    }

    /**
     * Handle the Sitemap "created" event.
     *
     * @param  \App\Models\Sitemap  $sitemap
     * @return void
     */
    public function created(Sitemap $sitemap)
    {
        //
    }

    /**
     * Handle the Sitemap "updated" event.
     *
     * @param  \App\Models\Sitemap  $sitemap
     * @return void
     */
    public function updated(Sitemap $sitemap)
    {
        //
    }

    /**
     * Handle the Sitemap "deleted" event.
     *
     * @param  \App\Models\Sitemap  $sitemap
     * @return void
     */
    public function deleted(Sitemap $sitemap)
    {
        //
    }

    /**
     * Handle the Sitemap "restored" event.
     *
     * @param  \App\Models\Sitemap  $sitemap
     * @return void
     */
    public function restored(Sitemap $sitemap)
    {
        //
    }

    /**
     * Handle the Sitemap "force deleted" event.
     *
     * @param  \App\Models\Sitemap  $sitemap
     * @return void
     */
    public function forceDeleted(Sitemap $sitemap)
    {
        //
    }
}
