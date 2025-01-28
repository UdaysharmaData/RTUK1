<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventCategoryPromotionalEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventCategoryPromotionalEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event category promotional event seeder logs');

        $this->truncateTable();

        $ecpes = DB::connection('mysql_2')->table('event_category_promotional_events')->get();

        foreach ($ecpes as $ecpe) {
            $eventCategory = EventCategory::find($ecpe->event_category_id);

            $_ecpe = EventCategoryPromotionalEvent::factory();

            if ($ecpe->events) {

                foreach(json_decode($ecpe->events) as $event_id) {
                    $foreignKeyColumns = [];
                    $_ecpe = EventCategoryPromotionalEvent::factory();

                    if ($event_id) { // check if the event exists
                        $event = Event::find($event_id);
                        $_ecpe = $_ecpe->for($event ?? Event::factory()->create(['id' => $event_id]));
        
                        if (!$event) {
                            Log::channel('dataimport')->debug("id: {$ecpe->id} The event id {$event_id} did not exists and was created. ecpe: ".json_encode($ecpe));
                        }
                    } else {
                        $foreignKeyColumns = ['event_id' => null];
                    }

                    $_ecpe = $_ecpe->for($eventCategory ?? EventCategory::factory()->create(['id' => $ecpe->event_category_id]))
                        ->create([
                            ...$foreignKeyColumns
                        ]);
                }
            } else {
                $foreignKeyColumns = ['event_id' => null];

                $_ecpe = $_ecpe->for($eventCategory ?? EventCategory::factory()->create(['id' => $ecpe->event_category_id]))
                    ->create([
                        ...$foreignKeyColumns
                    ]);
            }

            if (!$eventCategory) {
                Log::channel('dataimport')->debug("id: {$ecpe->id} The event category id {$ecpe->event_category_id} did not exists and was created. Event_category_promotional_event: ".json_encode($ecpe));
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
        EventCategoryPromotionalEvent::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
