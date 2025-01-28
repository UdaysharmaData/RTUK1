<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Enums\ResaleRequestStateEnum;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\ResalePlace;
use App\Modules\Charity\Models\ResaleRequest;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ResaleRequestSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The resale request seeder logs');

        $this->truncateTable();

        $requests = DB::connection('mysql_2')->table('resale_requests')->get();

        foreach ($requests as $request) {
            $_request = ResaleRequest::factory();

            $place = ResalePlace::find($request->resale_place_id);
            $_request = $_request->for($place ?? ResalePlace::factory()->create(['id' => $request->resale_place_id]));

            if (!$place) {
                Log::channel('dataimport')->debug("id: {$request->id} The place id {$request->resale_place_id} did not exists and was created. Resale_request: ".json_encode($request));
            }

            $charity = Charity::find($request->charity_id);
            $_request = $_request->for($place ?? Charity::factory()->create(['id' => $request->charity_id]));

            if (!$charity) {
                Log::channel('dataimport')->debug("id: {$request->id} The charity id {$request->charity_id} did not exists and was created. Resale_request: ".json_encode($request));
            }

            $_request = $_request->create([
                'state' => $this->valueOrDefault($request->state, ResaleRequestStateEnum::Accepted),
                'places' => $request->places,
                'unit_price' => $request->unit_price,
                'discount' => $this->valueOrDefault($request->discount),
                'contact_email' => $this->valueOrDefault($request->contact_email),
                'contact_phone' => $this->valueOrDefault($request->contact_phone),
                'note' => $this->valueOrDefault($request->note),
                'charge_id' => $this->valueOrDefault($request->charge_id)
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
        ResaleRequest::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
