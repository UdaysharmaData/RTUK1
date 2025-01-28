<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SyncUserRelatedPropertiesJob;

class SyncUserRelatedProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:sync-related-props {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync user related properties';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $pid = getmypid();

        try {
            $site = Site::where('name', $this->argument('site'))
                ->orWhere('domain', $this->argument('site'))
                ->orWhere('code', $this->argument('site'))
                ->firstOrFail();

            Cache::put('command-site-' . $pid,  $site, now()->addHour());

            User::chunk(100, function ($users) use ($site) {
                dispatch(new SyncUserRelatedPropertiesJob($users, $site));
            });

            Cache::forget('command-site-' . $pid);
            echo "Command ran successfully!";
        } catch (Exception $exception) {
            Log::error($exception);
            echo $exception->getMessage();
            Cache::forget('command-site-' . $pid);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
