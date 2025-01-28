<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use App\Modules\Setting\Models\Site;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SyncUserRelatedPropertiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $users;
    public $site;

    /**
     * Create a new job instance.
     * 
     * @param  mixed  $users
     * @param  Site   $site
     * @return void
     */
    public function __construct(mixed $users, Site $site)
    {
        $this->users = $users;
        $this->site = $site;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $pid = getmypid();

        Cache::put('command-site-' . $pid,  $this->site, now()->addHour());

        foreach ($this->users as $user) {
            $user->bootstrapUserRelatedProperties();
        }

        Cache::forget('command-site-' . $pid);
    }
}
