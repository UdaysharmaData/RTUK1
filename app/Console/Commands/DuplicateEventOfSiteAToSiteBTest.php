<?php

namespace App\Console\Commands;

use Schema;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use Illuminate\Support\Facades\Cache;
use App\Modules\Event\Models\EventCategory;
use App\Jobs\DuplicateEventOfSiteAToSiteBTestJob;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventEventCategory;

class DuplicateEventOfSiteAToSiteBTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:duplicate-to-site {siteA} {siteB}';

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
            $siteA = Site::where('name', $this->argument('siteA'))
                ->orWhere('domain', $this->argument('siteA'))
                ->orWhere('code', $this->argument('siteA'))
                ->firstOrFail();

            $siteB = Site::where('name', $this->argument('siteB'))
                ->orWhere('domain', $this->argument('siteB'))
                ->orWhere('code', $this->argument('siteB'))
                ->firstOrFail();

            Cache::put('command-site-' . $pid,  $siteB, now()->addHour());

            EventCategory::where('site_id', $siteA->id)->chunk(100, function ($categories) use ($siteA, $siteB) { // Create the event categories
                foreach ($categories as $category) {
                    $category = EventCategory::firstOrNew([
                        'name' => $category->name,
                        'site_id' => $siteB->id
                    ], [
                        'visibility' => $category->visibility,
                        'description' => $category->description,
                        'color' => $category->color,
                        'distance_in_km' => $category->distance_in_km
                    ]);
                    
                    $category->save();
                }
            });

            Event::whereHas('eventCategories', function ($query) use ($siteA) {
                $query->where('site_id', $siteA->id);
            })->chunk(100, function ($events) use ($siteA, $siteB) {
                foreach ($events as $event) {
                    dispatch(new DuplicateEventOfSiteAToSiteBTestJob($event, $siteA, $siteB));
                }
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
