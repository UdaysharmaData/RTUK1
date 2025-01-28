<?php

namespace Database\Seeders;

use DB;
use Schema;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Enums\CharityListingAdTypeEnum;
use App\Enums\CharityListingAdPositionEnum;
use App\Modules\Charity\Models\CharityListing;
use App\Modules\Charity\Models\CharityListingAd;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CharityListingAdSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The charity listing ad seeder logs');

        $this->truncateTable();

        $clas = DB::connection('mysql_2')->table('partner_listing_ads')->get();

        foreach ($clas as $cla) {
            $_cla = CharityListingAd::factory();

            $charityListing = CharityListing::find($cla->partner_listing_id);
            $_cla = $_cla->for($charityListing ?? CharityListing::factory()->create(['id' => $cla->partner_listing_id]));

            if (!$charityListing) {
                Log::channel('dataimport')->debug("id: {$cla->id} The charityListing id  {$cla->partner_listing_id} did not exists and was created. Charity_listing_ad: ".json_encode($cla));
            }

            $_cla = $_cla->create([
                'key' => $cla->key,
                'position' => $this->valueOrDefault($cla->position, CharityListingAdPositionEnum::Inline),
                'type' => $this->valueOrDefault($cla->type, CharityListingAdTypeEnum::Image),
                'link' => $cla->link,
            ]);

            if ($this->valueOrDefault($cla->path)) { // save the image/video path
                $image = $_cla->upload()->updateOrCreate([], [
                    'title' => $_cla->charityListing->title,
                    'type' => UploadTypeEnum::Image,
                    'use_as' => UploadUseAsEnum::Image,
                    'url' => config('app.images_path') . str_replace('uploads/', '', $cla->path),
                ]);

                if (Storage::disk('sfc')->exists($cla->path)) { // Copy the image
                    Storage::disk('local')->put('public'.$image->url, Storage::disk('sfc')->get($cla->path));
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
        CharityListingAd::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
