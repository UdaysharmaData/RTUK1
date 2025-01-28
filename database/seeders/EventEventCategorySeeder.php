<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\EventEventCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventEventCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event event category seeder logs');

        $this->truncateTable();

        EventEventCategory::factory()->count(100)->create();
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        EventEventCategory::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
