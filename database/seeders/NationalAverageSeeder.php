<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\NationalAverage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class NationalAverageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The national average seeder logs');

        $this->truncateTable();

        $nationalAvgs = DB::connection('mysql_2')->table('national_averages')->get();

        foreach ($nationalAvgs as $nationalAvg) {
            $category = EventCategory::find($nationalAvg->event_category_id);

            NationalAverage::factory()
                ->for($category ?? EventCategory::factory()->create(['id' => $nationalAvg->event_category_id]))
                ->create([
                    'gender' => $nationalAvg->gender,
                    'year' => $nationalAvg->year,
                    'time' => $nationalAvg->time
                ]);

            if (!$category) {
                Log::channel('dataimport')->debug("id: {$nationalAvg->id} The event category {$nationalAvg->event_category_id} did not exists and was created. National_average: ".json_encode($nationalAvg));
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
        NationalAverage::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
