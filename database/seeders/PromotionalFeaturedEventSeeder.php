<?php

namespace Database\Seeders;

use DB;
use Str;
use Schema;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\Event;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Event\Models\PromotionalFeaturedEvent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PromotionalFeaturedEventSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The promotional featured event seeder logs');

        $this->truncateTable();

        $pfes = DB::connection('mysql_2')->table('promotional_featured_events')->get();

        foreach ($pfes as $pfe) {
            if ($pfe->events) {
                foreach(json_decode($pfe->events) as $event_id) {
                    $foreignKeyColumns = [];
                    $event = Event::find($event_id);

                    $_pfe = PromotionalFeaturedEvent::factory();

                    if ($this->valueOrDefault($pfe->county)) {
                        $regionName = ucwords(Str::replace('_', ' ', Str::replace('_-_', ' ', $pfe->county)));
                        $region = Region::where('name', $regionName)->first();
                        $_pfe = $_pfe->for($region ?? Region::factory()->create(['name' => $regionName]));
                    } else {
                        $foreignKeyColumns = ['region_id' => null];
                    }

                    $_pfe = $_pfe->for($event ?? Event::factory()->create(['id' => $event_id]))
                        ->create([
                            ...$foreignKeyColumns,
                        ]);

                    if (!$event) {
                        Log::channel('dataimport')->debug("id: {$pfe->id} The event id {$event_id} did not exists and was created. Promotional_featured_event: ".json_encode($pfe));
                    }
                }
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
        PromotionalFeaturedEvent::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
