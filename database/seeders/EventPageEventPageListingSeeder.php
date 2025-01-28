<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\EventPageEventPageListing;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventPageEventPageListingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event page event page listing seeder logs');

        $this->truncateTable();

        EventPageEventPageListing::factory()->count(100)->create();
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        EventPageEventPageListing::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
