<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\Charity;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Charity\Models\CharityProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CharityProfileSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The charity profile seeder logs');

        $this->truncateTable();

        $charityProfiles = DB::connection('mysql_2')->table('charity_data')->get();

        foreach ($charityProfiles as $charityProfile) {
            $charity = Charity::find($charityProfile->charity_id);
            $site = Site::find($charityProfile->site_id);

            $charity = $charity ?? Charity::factory()->create(['id' => $charityProfile->charity_id]);
            $site = $site ?? Site::factory()->create(['id' => $charityProfile->site_id]);

            $doesntExits = CharityProfile::where('charity_id', $charity->id)->where('site_id', $site->id)->doesntExist();

            if ($doesntExits) {
                $_charityProfile = CharityProfile::factory()
                    ->for($charity)
                    ->for($site)
                    ->create([
                        'description' => $charityProfile->description,
                        'mission_values' => $charityProfile->mission_values,
                        'video' => $charityProfile->video
                    ]);
            } else {
                Log::channel('dataimport')->debug("Integrity constraint violation: id: {$charityProfile->id} The charity id  {$charityProfile->charity_id} and site id {$charityProfile->site_id} exists: ".json_encode($charityProfile));
            }

            if (!$charity) {
                Log::channel('dataimport')->debug("id: {$charityProfile->id} The charity id  {$charityProfile->charity_id} did not exists and was created. Charity_profile: ".json_encode($charityProfile));
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
        CharityProfile::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
