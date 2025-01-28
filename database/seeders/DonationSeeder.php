<?php

namespace Database\Seeders;

use DB;
use Schema;
use Database\Traits\FormatDate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\Donation;
use App\Modules\Corporate\Models\Corporate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DonationSeeder extends Seeder
{
    use FormatDate;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The donation seeder logs');

        $this->truncateTable();

        $donations = DB::connection('mysql_2')->table('donations')->get();

        foreach ($donations as $donation) {
            $charity = null;
            $corporate = null;
            $foreignKeyColumns = [];

            $_donation = Donation::factory();

            if ($donation->charity_id) { // check if the charity exists
                $charity = Charity::find($donation->charity_id);
                $_donation = $_donation->for($charity ?? Charity::factory()->create(['id' => $donation->charity_id]));

                if (!$charity) {
                    Log::channel('dataimport')->debug("id: {$donation->id} The charity id  {$donation->charity_id} did not exists and was created. Donation: ".json_encode($donation));
                }
            } else {
                $foreignKeyColumns = ['charity_id' => null];
            }

            if ($donation->corporate_id) { // check if the corporate exists
                $corporate = Corporate::find($donation->corporate_id);
                $_donation = $_donation->for($corporate ?? Corporate::factory()->create(['id' => $donation->corporate_id]));

                if (!$corporate) {
                    Log::channel('dataimport')->debug("id: {$donation->id} The corporate id  {$donation->corporate_id} did not exists and was created. Donation: ".json_encode($donation));
                }
            } else {
                $foreignKeyColumns = ['corporate_id' => null, ...$foreignKeyColumns];
            }

            $_donation = $_donation->create([
                ...$foreignKeyColumns,
                'amount' => $donation->amount,
                'conversion_rate' => $donation->conversion_rate,
                'expires_at' => $this->dateOrNull($donation->expires_at)
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
        Donation::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
