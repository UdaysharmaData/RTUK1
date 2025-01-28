<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\FundraisingEmail;
use App\Modules\Charity\Models\CharityFundraisingEmail;
use App\Modules\Charity\Models\CharityFundraisingEmailEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CharityFundraisingEmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The charity fundraising email seeder logs');

        $this->truncateTable();

        $cfes = DB::connection('mysql_2')->table('charity_drips')->get();

        foreach ($cfes as $cfe) {
            $charity = Charity::find($cfe->charity_id);
            $fundraisingEmail = FundraisingEmail::find($cfe->drip_id);

            $_cfe = CharityFundraisingEmail::factory()
                ->for($charity ?? Charity::factory()->create(['id'=>$cfe->charity_id]))
                ->for($fundraisingEmail ?? FundraisingEmail::factory()->create(['id'=>$cfe->drip_id]))
                ->create([
                    'status' => $cfe->status,
                    'content' => $cfe->content,
                    'from_name' => $cfe->from_name,
                    'from_email' => $cfe->from_email,
                    'all_events' => 0
                ]);

            if ($cfe->events) { // Save the events
                $eventsIds = json_decode($cfe->events);
                if ($eventsIds[0] == 0) { // The charity fundraising email is for all the events
                    $_cfe->all_events = 1;
                    $_cfe->save();
                } else {
                    foreach ($eventsIds as $event_id) {
                        $event = Event::find($event_id);

                        CharityFundraisingEmailEvent::factory()
                            ->for($_cfe)
                            ->for($event ?? Event::factory()->create(['id' => $event_id]))
                            ->create();

                        if (!$event) {
                            Log::channel('dataimport')->debug("id: {$cfe->id} The event id  {$event_id} did not exists and was created. Charity_fundraising_email: ".json_encode($cfe));
                        }
                    }
                }
            }

            if (!$charity) {
                Log::channel('dataimport')->debug("id: {$cfe->id} The charity id  {$cfe->charity_id} did not exists and was created. Charity_fundraising_email: ".json_encode($cfe));
            }

            if (!$fundraisingEmail) {
                Log::channel('dataimport')->debug("id: {$cfe->id} The fundraising email id  {$cfe->drip_id} did not exists and was created. Charity_fundraising_email: ".json_encode($cfe));
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
        CharityFundraisingEmail::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
