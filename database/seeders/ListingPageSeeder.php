<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventPage;
use App\Modules\Event\Models\ListingPage;
use App\Enums\CharityCharityListingTypeEnum;
use App\Modules\Charity\Models\CharityListing;
use App\Modules\Charity\Models\CharityCharityListing;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ListingPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The listing page seeder logs');

        $this->truncateTable();

        $listingPages = DB::connection('mysql_2')->table('listings_pages')->get();

        foreach ($listingPages as $page) {
            $charityListing = CharityListing::find($page->partner_listing_id);
            $foreignKeyColumns = [];

            $_page = ListingPage::factory();

            if ($page->event_id) { // check if the event exists
                $event = Event::find($page->event_id);
                $_page = $_page->for($event ?? Event::factory()->create(['id' => $page->event_id]));

                if (!$event) {
                    Log::channel('dataimport')->debug("id: {$page->id} The event id {$page->event_id} did not exists and was created. Listing_page: ".json_encode($page));
                }
            } else {
                $foreignKeyColumns = ['event_id' => null];
            }

            // Create the listing page
            $_page = $_page->for($charityListing ?? CharityListing::factory(['id' => $page->partner_listing_id, 'description' => null, 'url' => null, 'video' => null, 'show_2_year_members' => 1, 'primary_color' => null, 'secondary_color' => null, 'charity_custom_title' => null, 'primary_partner_charities_custom_title' => null, 'secondary_partner_charities_custom_title' => null ]))
                ->create([
                    ...$foreignKeyColumns,
                    'id' => $page->id,
                    'title' => $page->title,
                    'type' => $page->type,
                    'event_page_description' => $page->event_page_description
                ]);

            if ($_page) { // Get the event pages created by the listing page and update the event_page_id on the charity_charity_listing table. NB: the event_page_listings_pages table has been removed from the new database and the event_page_id column added to the charity_charity_listings table as most of the data it saved was redundant with the data on the event_page_listings_pages table.
                $eplps = DB::connection('mysql_2')->table('event_page_listings_pages')->where('listings_page_id', $_page->id)->get();

                foreach ($eplps as $eplp) { // Update the charitycharitylisting
                    $charity = Charity::find($eplp->charity_id);
                    $eventPage = EventPage::find($eplp->event_page_id);

                    if (!$charity) {
                        $charity = Charity::factory()->create(['id' => $eplp->charity_id]);
                        Log::channel('dataimport')->debug("id: {$page->id}. Event page listings pages id: {$eplp->id}. The charity id  {$eplp->charity_id} did not exists and was created. Listing_page: ".json_encode($page));
                    }

                    if (!$eventPage) {
                        $eventPage = EventPage::factory()->create(['id' => $eplp->event_page_id]);
                        Log::channel('dataimport')->debug("id: {$page->id}. Event page listings pages id: {$eplp->id}. The event page id  {$eplp->event_page_id} did not exists and was created. Listing_page: ".json_encode($page));
                    }
                    
                    $ccl = CharityCharityListing::where('charity_listing_id', $_page->charity_listing_id)->where('charity_id', $charity->id)->first();

                    if ($ccl) { // Update the event_page_id
                        $ccl->update([
                            'event_page_id' => $eventPage->id
                        ]);
                    } else {
                        // This block of code was commented because it is not needed since the CharityListingSeeder would have been run before the ListingPageSeeder and the primary_partner, secondary_partner and two_year (having a custom url) charities created in the charity_charity_listings table. So, in case the above condition doesn't hold, the record under this condition below should be of type two_year (according to the new design of the database).

                        // $type = null;
                        // // Get the charity listing from the old database.
                        // $oldCharityListing = DB::connection('mysql_2')->table('partner_listings')->where('id', $_page->charity_listing_id)->first();

                        // if ($oldCharityListing) {
                        //     if ($oldCharityListing->partner_charities && in_array($charity->id, json_decode($oldCharityListing->partner_charities))) { // Check if the eplp (event page listing page) charity_id is associated to the listing page as a partner charity
                        //         $type = CharityCharityListingTypeEnum::PrimaryPartner;
                        //         Log::channel('dataimport')->debug('listing_page_id:'.$page->id.' is of type primary partner charity');
                        //     }

                        //     if ($oldCharityListing->secondary_partner_charities && in_array($charity->id, json_decode($oldCharityListing->secondary_partner_charities))) { // Check if the eplp (event page listing page) charity_id is associated to the listing page as a secondary charity
                        //         $type = CharityCharityListingTypeEnum::SecondaryPartner;
                        //         Log::channel('dataimport')->debug('listing_page_id:'.$page->id.' is of type secondary partner charity');
                        //     }
                        // }

                        CharityCharityListing::factory()
                            ->for($_page->charityListing)
                            ->for($charity)
                            ->for($eventPage)
                            ->create([
                                // 'type' => $type ?? CharityCharityListingTypeEnum::TwoYear,
                                'type' => CharityCharityListingTypeEnum::TwoYear,
                                'url' => null,
                            ]);

                        Log::channel('dataimport')->debug("id: {$page->id}. Event page listings pages id: {$eplp->id}. The charity charity listing having the charity id {$eplp->charity_id} did not exists and was created with the type set to ".(CharityCharityListingTypeEnum::TwoYear)->name.". Listing_page: ".json_encode($page));
                    }
                }
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
        ListingPage::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
