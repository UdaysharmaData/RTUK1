<?php

namespace Database\Seeders;

use DB;
use Str;
use Schema;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Database\Traits\SlugTrait;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventPage;
use App\Modules\Corporate\Models\Corporate;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Event\Models\EventPageListing;
use App\Modules\Event\Models\EventPageEventPageListing;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventPageListingSeeder extends Seeder
{
    use EmptySpaceToDefaultData, SlugTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event page listing seeder logs');

        $this->truncateTables();

        $epls = DB::connection('mysql_2')->table('event_page_listings')->get();

        foreach ($epls as $epl) {
            $charity = Charity::find($epl->epl_charity);
            $corporate = Corporate::find($epl->corporate_id);
            $foreignKeyColumns = [];

            $_epl = EventPageListing::factory();

            if ($epl->corporate_id) { // check if the corporate exists
                $corporate = Corporate::find($epl->corporate_id);
                $_epl = $_epl->for($corporate ?? Corporate::factory()->create(['id' => $epl->corporate_id]));

                if (!$corporate) {
                    Log::channel('dataimport')->debug("id: {$epl->id} The corporate id {$epl->corporate_id} did not exists and was created. Event_page_listing: ".json_encode($epl));
                }
            } else {
                $foreignKeyColumns = ['corporate_id' => null];
            }

            $_epl = $_epl->for($charity ?? Charity::factory()->create(['id' => $epl->epl_charity]))
                ->create([
                    ...$foreignKeyColumns,
                    'id' => $epl->id,
                    'title' => $epl->title,
                    // 'slug' => $this->getUniqueSlug(EventPageListing::class, $this->valueOrDefault($epl->slug, Str::slug($epl->title))), // TODO: Revis the getUniqueSlug to avoid the ERROR MESSAGE: String data, right truncated: 1406 Data too long for column 'slug' at row 1 virtual-running-events__________________________________________________________________________________________________________________________________________________________________________________________________________________________________________
                    'slug' => $this->getUniqueSlug(EventPageListing::class, $this->valueOrDefault($epl->slug, Str::slug($epl->title)).Str::random(5)),
                    'description' => $epl->description,
                    'other_events' => $epl->other_events,
                    'primary_color' => $epl->primary_color,
                    'secondary_color' => $epl->secondary_color,
                    'background_image' => $epl->background_image
                ]);

                if ($epl->featured_event_pages) { // Create the featured event pages of the event page listing

                    foreach (json_decode($epl->featured_event_pages) as $event_page_id) {
                        $eventPage = EventPage::find($event_page_id);
                        $eventPageEventPageListing = EventPageEventPageListing::factory()
                            ->for($_epl)
                            ->for($eventPage ?? EventPage::factory()->create(['id' => $event_page_id]));

                            if ($eventPage) { // Check if the featured event has a custom image and video, and add it before saving.
                                $exists = DB::connection('mysql_2')->table('event_page_event_page_listings')->where('event_page_listing_id', $epl->id)->where('event_page_id', $event_page_id)->first();

                                if ($exists) {
                                    $eventPageEventPageListing = $eventPageEventPageListing->create([
                                        'video' => $this->valueOrDefault($exists?->video)
                                    ]);

                                    if ($this->valueOrDefault($exists->image)) { // save the image path
                                        $eventPageEventPageListing->upload()->updateOrCreate([], [
                                            'title' => $eventPageEventPageListing->eventPageListing->title,
                                            'type' => UploadTypeEnum::Image,
                                            'use_as' => UploadUseAsEnum::Image,
                                            'url' => config('app.images_path') . str_replace('uploads/', '', $exists->image)
                                        ]);
                                    }
                                }
                            } else {
                                $eventPageEventPageListing->create();

                                Log::channel('dataimport')->debug("id: {$epl->id} The event page id  {$event_page_id} did not exists and was created. Event_page_listing: ".json_encode($epl));
                            }
                    }
                }

            if (!$charity) {
                Log::channel('dataimport')->debug("id: {$epl->id} The charity id  {$epl->epl_charity} did not exists and was created. Event_page_listing: ".json_encode($epl));
            }
        }
    }

    /**
     * Truncate the tables
     *
     * @return void
     */
    public function truncateTables()
    {
        Schema::disableForeignKeyConstraints();
        EventPageListing::truncate();
        EventPageEventPageListing::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
