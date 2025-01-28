<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Modules\Charity\Models\CharityFundraisingEmailEvent;

class CharityFundraisingEmailEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The charity fundraising email event seeder logs');

        $this->truncateTable();

        CharityFundraisingEmailEvent::factory()->count(100)->create();
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        CharityFundraisingEmailEvent::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
