<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Modules\Event\Models\EventPageEventCategoryEventPageListing;

class EventPageEventCategoryEventPageListingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event page event category event page listing seeder logs');

        $this->truncateTable();

        EventPageEventCategoryEventPageListing::factory()->count(100)->create();
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        EventPageEventCategoryEventPageListing::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
