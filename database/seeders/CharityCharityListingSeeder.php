<?php

namespace Database\Seeders;

use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Charity\Models\CharityCharityListing;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CharityCharityListingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The charity charity listing seeder logs');

        $this->truncateTable();

        CharityCharityListing::factory()->count(100)->create();
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        CharityCharityListing::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
