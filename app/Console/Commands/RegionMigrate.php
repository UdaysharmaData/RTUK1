<?php

namespace App\Console\Commands;

use App\Models\EventCityLinking;
use App\Models\EventRegionLinking;
use App\Models\EventVenuesLinking;
use App\Models\Region;
use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class RegionMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'region:migrate {site}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'All Region Migrations Into Another Region Table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $site = Site::where('name', $this->argument('site'))
                ->orWhere('domain', $this->argument('site'))
                ->orWhere('code', $this->argument('site'))
                ->firstOrFail();

            $siteId = $site->id;

            // Process events for regions
            Event::where('status', Event::ACTIVE)
                ->whereNotNull('region_id')
                ->whereHas('eventCategories.site', function ($query) use ($siteId) {
                    $query->where('id', $siteId);
                })
                ->chunk(500, function ($events) use ($siteId) {
                    foreach ($events as $event) {
                        EventRegionLinking::create([
                            'ref' => Str::orderedUuid()->toString(),
                            'site_id' => $siteId,
                            'event_id' => $event->id,
                            'region_id' => $event->region_id,
                        ]);
                    }
                });

            // Process events for cities
            Event::where('status', Event::ACTIVE)
                ->whereNotNull('region_id')
                ->whereNotNull('city_id')
                ->whereHas('eventCategories.site', function ($query) use ($siteId) {
                    $query->where('id', $siteId);
                })
                ->chunk(500, function ($events) use ($siteId) {
                    foreach ($events as $event) {
                        EventCityLinking::create([
                            'ref' => Str::orderedUuid()->toString(),
                            'site_id' => $siteId,
                            'event_id' => $event->id,
                            'city_id' => $event->city_id,
                        ]);
                    }
                });

            // Process events for venues
            Event::where('status', Event::ACTIVE)
                ->whereNotNull('region_id')
                ->whereNotNull('venue_id')
                ->whereHas('eventCategories.site', function ($query) use ($siteId) {
                    $query->where('id', $siteId);
                })
                ->chunk(500, function ($events) use ($siteId) {
                    foreach ($events as $event) {
                        EventVenuesLinking::create([
                            'ref' => Str::orderedUuid()->toString(),
                            'site_id' => $siteId,
                            'event_id' => $event->id,
                            'venue_id' => $event->venue_id,
                        ]);
                    }
                });

            $this->info('Events processed successfully for site: ' . $this->argument('site'));
        } catch (ModelNotFoundException $e) {
            $this->error('Site not found with the specified identifier: ' . $this->argument('site'));
        } catch (Exception $e) {
            $this->error('An error occurred while processing events: ' . $e->getMessage());
        }
    }

}
