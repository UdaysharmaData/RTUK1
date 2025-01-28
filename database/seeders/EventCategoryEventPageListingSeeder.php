<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\EventPage;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventPageListing;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Modules\Event\Models\EventCategoryEventPageListing;
use App\Modules\Event\Models\EventPageEventCategoryEventPageListing;

class EventCategoryEventPageListingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event category event page listing seeder logs');

        $this->truncateTable();

        $ecepls = DB::connection('mysql_2')->table('event_category_event_page_listings')->get();
        
        foreach ($ecepls as $ecepl) {
            $epl = EventPageListing::find($ecepl->event_page_listing_id);
            $ec = EventCategory::find($ecepl->event_category_id);

            $_ecepl = EventCategoryEventPageListing::factory()
                ->for($epl ?? EventPageListing::factory()->create(['id' => $ecepl->event_page_listing_id]))
                ->for($ec ?? EventCategory::factory()->create(['id' => $ecepl->event_category_id]))
                ->create([
                    'id' => $ecepl->id,
                    'priority' => $ecepl->priority
                ]);

            if ($ecepl->event_pages) { // Create the event pages of the event category
                foreach (json_decode($ecepl->event_pages) as $event_page_id) {
                    $eventPage = EventPage::find($event_page_id);

                    EventPageEventCategoryEventPageListing::factory()
                        ->for($_ecepl)
                        ->for($eventPage ?? EventPage::factory()->create(['id' => $event_page_id]))
                        ->create();

                    if (!$eventPage) {
                        Log::channel('dataimport')->debug("id: {$ecepl->id} The event page id  {$event_page_id} did not exists and was created. Campaign: ".json_encode($ecepl));
                    }
                }
            }

            if (!$epl) {
                Log::channel('dataimport')->debug("id: {$ecepl->id} The event page listing id  {$ecepl->event_page_listing_id} did not exists and was created. Event_category_event_page_listing: ".json_encode($ecepl));
            }

            if (!$ec) {
                Log::channel('dataimport')->debug("id: {$ecepl->id} The event category id  {$ecepl->event_category_id} did not exists and was created. Event_category_event_page_listing: ".json_encode($ecepl));
            }
        }
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        EventCategoryEventPageListing::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
