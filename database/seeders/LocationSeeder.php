<?php

namespace Database\Seeders;

use DB;
use Schema;
use App\Models\Location;
use Illuminate\Database\Seeder;
use App\Modules\Event\Models\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The location seeder logs');

        $this->truncateTable();

        Location::factory()->count(100)->create([
            'locationable_type' => Event::class,
            'locationable_id' => Event::inRandomOrder()->value('id')
        ]);
    }

    /**
     * Truncate the table
     *
     * @return void
     */
    public function truncateTable()
    {
        Schema::disableForeignKeyConstraints();
        Location::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
