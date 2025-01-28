<?php

namespace Database\Seeders;

use App\Modules\Event\Models\Event;
use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\EventManager;
use App\Modules\Event\Models\EventEventManager;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class EventEventManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The event event manager seeder logs');

        $this->truncateTable();

        $managers = DB::connection('mysql_2')->table('event_manager')->get();
        
        foreach ($managers as $manager) {
            $event = Event::find($manager->event_id);

            // Get the event_manager_id of the user (event_manager)
            $eventManager = EventManager::where('user_id', $manager->manager_id)->first();

            EventEventManager::factory()
                ->for($event ?? Event::factory()->create(['id' => $manager->event_id]))
                ->for($eventManager ?? EventManager::factory()->create(['user_id' => $manager->manager_id]))
                ->create();

            if (!$event) {
                Log::channel('dataimport')->debug("id: {$manager->id} The event id {$manager->event_id} did not exists and was created. Event_event_manager: ".json_encode($manager));
            }

            if (!$eventManager) {
                Log::channel('dataimport')->debug("id: {$manager->id} The user id {$manager->manager_id} did not exists and was created. Event_event_manager: ".json_encode($manager));
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
        EventEventManager::truncate();
        Schema::enableForeignKeyConstraints();
    }
}