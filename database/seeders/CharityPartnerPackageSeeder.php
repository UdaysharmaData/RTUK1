<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Charity\Models\Charity;
// use App\Modules\User\Models\Contract;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Charity\Models\PartnerPackage;
use App\Enums\CharityPartnerPackageStatusEnum;
use App\Modules\Charity\Models\CharityPartnerPackage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CharityPartnerPackageSeeder extends Seeder
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

        Log::channel('dataimport')->debug('The charity partner package seeder logs');

        $cpps = DB::connection('mysql_2')->table('assigned_partner_packages')->get();

        foreach ($cpps as $cpp) {
            $foreignKeyColumns = [];

            $_ap = CharityPartnerPackage::factory();

            if ($cpp->partner_package_id) { // check if the partner package exists
                $package = PartnerPackage::find($cpp->partner_package_id);
                $_ap = $_ap->for($package ?? PartnerPackage::factory()->create(['id' => $cpp->partner_package_id]));

                if (!$package) {
                    Log::channel('dataimport')->debug("id: {$cpp->id} The partner package id  {$cpp->partner_package_id} did not exists and was created. Charity_partner_package: ".json_encode($cpp));
                }
            } else {
                $foreignKeyColumns = ['partner_package_id' => null];
            }

            if ($cpp->charity_id) { // check if the charity exists
                $charity = Charity::find($cpp->charity_id);
                $_ap = $_ap->for($charity ?? Charity::factory()->create(['id' => $cpp->charity_id]));

                if (!$charity) {
                    Log::channel('dataimport')->debug("id: {$cpp->id} The charity id  {$cpp->charity_id} did not exists and was created. Charity_partner_package: ".json_encode($cpp));
                }
            } else {
                $foreignKeyColumns = ['charity_id' => null, ...$foreignKeyColumns];
            }

            // if ($cpp->contract_id) { // check if the contract exists
            //     $contract = Contract::find($cpp->contract_id);
            //     $_ap = $_ap->for($contract ?? Contract::factory()->create(['id' => $cpp->contract_id]));

            //     if (!$contract) {
            //         Log::channel('dataimport')->debug('id:'. $cpp->id .' The contract id '. json_encode($cpp->contract_id). ' did not exists and was created');
            //     }
            // } else {
            //     $foreignKeyColumns = ['contract_id' => null, ...$foreignKeyColumns];
            // }

            $_ap = $_ap->create([
                ...$foreignKeyColumns,
                'status' => $this->valueOrDefault($cpp->status, CharityPartnerPackageStatusEnum::Assigned),
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
        CharityPartnerPackage::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
