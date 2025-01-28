<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Enums\CharityEventTypeEnum;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\CharityEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CharityEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The charity event seeder logs');

        $this->truncateTable();

        $charityEvents = DB::connection('mysql_2')->table('charity_places')->get();

        foreach ($charityEvents as $charityEvent) {
            $event = Event::find($charityEvent->event_id);
            $charity = Charity::find($charityEvent->charity_id);

            // $doesntExist = CharityEvent::where('event_id', $charityEvent->event_id)->where('charity_id', $charityEvent->charity_id)->where('type', CharityEventTypeEnum::Included)->doesntExist();

            // if ($doesntExist) {
                CharityEvent::Factory()
                    ->for($event ?? Event::factory()->create(['id' => $charityEvent->event_id]))
                    ->for($charity ?? Charity::factory()->create(['id' => $charityEvent->charity_id]))
                    ->create([
                        'type' => CharityEventTypeEnum::Included
                    ]);
            // }

            if (!$charity) {
                Log::channel('dataimport')->debug("id: {$charityEvent->id} The charity id  {$charityEvent->charity_id} did not exists and was created. CharityEvent: ".json_encode($charityEvent));
            }

            if (!$event) {
                Log::channel('dataimport')->debug("id: {$charityEvent->id} The event id  {$charityEvent->event_id} did not exists and was created. CharityEvent: ".json_encode($charityEvent));
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
        // CharityEvent::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
