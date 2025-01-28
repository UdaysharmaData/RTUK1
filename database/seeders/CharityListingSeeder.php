<?php

namespace Database\Seeders;

use DB;
use Str;
use Schema;
use Database\Traits\SlugTrait;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Charity\Models\Charity;
use Database\Traits\EmptySpaceToDefaultData;
use App\Enums\CharityCharityListingTypeEnum;
use App\Modules\Charity\Models\CharityListing;
use App\Modules\Charity\Models\CharityCharityListing;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CharityListingSeeder extends Seeder
{
    use EmptySpaceToDefaultData, SlugTrait;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The charity listing seeder logs');

        $this->truncateTable();

        $cls = DB::connection('mysql_2')->table('partner_listings')->get();

        foreach ($cls as $cl) {

            $_cl = CharityListing::factory();

            if ($cl->main_charity) { // check if the charity exists
                $mainCharity = Charity::find($cl->main_charity);
                $_cl = $_cl->for($mainCharity ?? Charity::factory()->create(['id' => $cl->main_charity]));

                if (!$mainCharity) {
                    Log::channel('dataimport')->debug("id: {$cl->id} The charity id  {$cl->main_charity} did not exists and was created. Charity_listing: ".json_encode($cl));
                }
            } else { // create the charity since the charity_id is required
                $_cl = $_cl->for(Charity::factory()->create(['id' => $cl->main_charity]));
                Log::channel('dataimport')->debug("id: {$cl->id} The charity id was null and a charity was created since it is required. Charity_listing: ".json_encode($cl));
            }

            $_cl = $_cl->create([ // Create the charity listing
                'id' => $cl->id,
                'title' => $cl->title,
                'slug' => $this->getUniqueSlug(CharityListing::class, $this->valueOrDefault($cl->slug, Str::slug($cl->title))),
                'description' => $this->valueOrDefault($cl->description),
                'url' => $this->valueOrDefault($cl->main_charity_custom_url),
                'video' => $this->valueOrDefault($cl->main_charity_video),
                'show_2_year_members' => $this->valueOrDefault($cl->show_2_year_members, 1),
                'primary_color' => $this->valueOrDefault($cl->primary_color),
                'secondary_color' => $this->valueOrDefault($cl->secondary_color),
                'charity_custom_title' => $cl->main_charity_ct,
                'primary_partner_charities_custom_title' => $cl->partner_charities_ct,
                'secondary_partner_charities_custom_title' => $cl->secondary_partner_charities_ct,
            ]);

            if ($cl->partner_charities) { // Create the primary partner charities
                foreach (json_decode($cl->partner_charities) as $charity_id) {
                    $charity = Charity::find($charity_id);

                    CharityCharityListing::factory()
                        ->for($_cl)
                        ->for($charity ?? Charity::factory()->create(['id' => $charity_id]))
                        ->create([
                            'event_page_id' => null,
                            'type' => CharityCharityListingTypeEnum::PrimaryPartner,
                            'url' => $this->getPartnerCharityUrl($cl->id, $charity_id),
                        ]);

                    if (!$charity) {
                        Log::channel('dataimport')->debug("id: {$cl->id} The primary partner charity id {$charity_id} did not exists and was created. Charity_listing: ".json_encode($cl));
                    }
                }
            }

            if ($cl->secondary_partner_charities) { // Create the secondary partner charities
                foreach (json_decode($cl->secondary_partner_charities) as $charity_id) {
                    $charity = Charity::find($charity_id);

                    CharityCharityListing::factory()
                        ->for($_cl)
                        ->for($charity ?? Charity::factory()->create(['id' => $charity_id]))
                        ->create([
                            'event_page_id' => null,
                            'type' => CharityCharityListingTypeEnum::SecondaryPartner,
                            'url' => $this->getPartnerCharityUrl($cl->id, $charity_id),
                        ]);

                    if (!$charity) {
                        Log::channel('dataimport')->debug("id: $cl->id The secondary partner charity id {$charity_id} did not exists and was created. Charity_listing: ".json_encode($cl));
                    }
                }
            }

            if ($cl->show_2_year_members) { // Create the 2_year charities having a custom url
                $charityCharityListing = DB::connection('mysql_2')->table('charity_partner_listings')->where('partner_listing_id', $cl->id)->where('type', 'two_year')->get();

                foreach ($charityCharityListing as $ccl) {
                    $charity = Charity::find($ccl->charity_id);

                    CharityCharityListing::factory()
                        ->for($_cl)
                        ->for($charity ?? Charity::factory()->create(['id' => $ccl->charity_id]))
                        ->create([
                            'event_page_id' => null,
                            'type' => CharityCharityListingTypeEnum::TwoYear,
                            'url' => $this->getPartnerCharityUrl($cl->id, $ccl->charity_id),
                        ]);
                }
            }
        }
    }

    /**
     * Get the partner charity url (custom url).
     * Check if the partner charity has a custom url (from the charity_partner_listings table) for the charity listing.
     * 
     * @param int $charity_listing_id
     * @param int $charity_id
     * @return string|null
     */
    private function getPartnerCharityUrl(int $charity_listing_id, int $charity_id)
    {
        $charityCharityListing = DB::connection('mysql_2')->table('charity_partner_listings')->where('partner_listing_id', $charity_listing_id)->where('charity_id', $charity_id)->first();

        return $charityCharityListing?->url ?? null;
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        CharityListing::truncate();
        CharityCharityListing::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
