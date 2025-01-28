<?php

namespace Database\Seeders;

use DB;
use Schema;
use Storage;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Partner\Models\Partner;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Charity\Models\PartnerPackage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PartnerPackageSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncateTable();

        Log::channel('dataimport')->debug('The partner package seeder logs');

        $packages = DB::connection('mysql_2')->table('partner_packages')->get();

        foreach ($packages as $package) {
            $foreignKeyColumns = [];

            $_package = PartnerPackage::factory();

            if ($package->partner_id) { // check if the partner exists
                $partner = Partner::find($package->partner_id);
                $_package = $_package->for($partner ?? Partner::factory()->create(['id' => $package->partner_id]));

                if (!$partner) {
                    Log::channel('dataimport')->debug("id: {$package->id} The partner id {$package->partner_id} did not exists and was created. Partner_package: ".json_encode($package));
                }
            } else {
                $foreignKeyColumns = ['partner_id' => null];
            }

            $_package = $_package->create([
                ...$foreignKeyColumns,
                'name' => $package->name,
                'price' => $package->price,
                'quantity' => $package->quantity,
                'start_date' => $package->start_date,
                'end_date' => $package->end_date,
                'renewal_date' => $package->renewal_date,
                'description' => $package->description,
                'price_commission' => $package->price_commission,
                'renewal_commission' => $package->renewal_commission,
                'new_business_commission' => $package->new_business_commission,
                'partner_split_after_commission' => $package->partner_split_after_commission,
                'rfc_split_after_commission' => $package->rfc_split_after_commission,
                'renewed_at' => $package->renewed_at
            ]);

            if ($this->valueOrDefault($package->image)) { // save the image path
                $image = $_package->upload()->updateOrCreate([], [
                    'title' => $_package->name,
                    'type' => UploadTypeEnum::PDF,
                    'use_as' => UploadUseAsEnum::Image,
                    'url' => config('app.images_path') . str_replace('uploads/', '', $package->image),
                ]);

                if (Storage::disk('sfc')->exists($package->image)) { // Copy the image
                    Storage::disk('local')->put('public'.$image->url, Storage::disk('sfc')->get($package->image));
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
        PartnerPackage::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
