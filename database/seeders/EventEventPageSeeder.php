<?php

namespace Database\Seeders;

use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\EventEventPage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventEventPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event event page seeder logs');

        $this->truncateTable();

        EventEventPage::factory()->count(100)->create();
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        EventEventPage::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
