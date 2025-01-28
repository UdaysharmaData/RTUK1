<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Charity\Models\Charity;
use App\Enums\ListingPageCharityTypeEnum;
use App\Modules\Event\Models\ListingPageCharity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ListingPageCharitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Used to preset the primary and secondary partners used when creating the listing pages.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The listing page charity seeder logs');

        $this->truncateTable();

        $lpcs = DB::connection('mysql_2')->table('listings_pages_charities')->get();

        foreach ($lpcs as $lpc) {
            if ($lpc->charities) {
                foreach(json_decode($lpc->charities) as $charity_id) {
                    $charity = Charity::find($charity_id);

                    ListingPageCharity::factory()
                        ->for($charity ?? Charity::factory()->create(['id' => $charity_id]))
                        ->create([
                            'type' => $lpc->type == 'partner' ? ListingPageCharityTypeEnum::PrimaryPartner : $lpc->type
                        ]);

                    if (!$charity) {
                        Log::channel('dataimport')->debug("id: {$lpc->id} The charity id {$charity_id} did not exists and was created. Listing_page_charity: ".json_encode($lpc));
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
        ListingPageCharity::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
