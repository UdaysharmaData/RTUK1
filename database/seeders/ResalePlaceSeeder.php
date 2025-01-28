<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\ResalePlace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ResalePlaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The resale place seeder logs');

        $this->truncateTable();

        $places = DB::connection('mysql_2')->table('resale_places')->get();

        foreach ($places as $place) {
            $_place = ResalePlace::factory();

            $charity = Charity::find($place->charity_id);
            $_place = $_place->for($charity ?? Charity::factory()->create(['id' => $place->charity_id]));

            if (!$charity) {
                Log::channel('dataimport')->debug("id: {$place->id} The charity id {$place->charity_id} did not exists and was created. Resale_place: ".json_encode($place));
            }

            $event = Event::find($place->event_id);
            $_place = $_place->for($event ?? Event::factory()->create(['id' => $place->event_id]));

            if (!$event) {
                Log::channel('dataimport')->debug("id: {$place->id} The event id {$place->event_id} did not exists and was created. Resale_place: ".json_encode($place));
            }

            $_place = $_place->create([
                'places' => $place->places,
                'taken' => $place->taken,
                'unit_price' => $place->unit_price,
                'discount' => $place->discount
            ]);
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
        ResalePlace::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
