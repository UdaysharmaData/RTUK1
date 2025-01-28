<?php

namespace Database\Seeders;

use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Charity\Models\CampaignEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CampaignEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The campaign event seeder logs');

        $this->truncateTable();

        CampaignEvent::factory()->count(100)->create();
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        CampaignEvent::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
