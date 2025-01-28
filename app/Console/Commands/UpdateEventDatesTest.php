<?php

namespace App\Console\Commands;

use Schema;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\Cache;
use App\Jobs\UpdateEventDatesTestJob;
use App\Modules\Event\Models\EventEventCategory;

class UpdateEventDatesTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:update-dates {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update event dates for a site.';

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

            EventEventCategory::chunk(100, function ($eecs) use ($site) {
                dispatch(new UpdateEventDatesTestJob($eecs, $site));
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
