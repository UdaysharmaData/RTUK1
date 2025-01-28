<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Charity\Models\FundraisingEmail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FundraisingEmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The fundraising email seeder logs');

        $this->truncateTable();

        $fundraisingEmails = DB::connection('mysql_2')->table('drips')->get();

        foreach ($fundraisingEmails as $fundraisingEmail) {
            FundraisingEmail::factory()
                ->create([
                    'status' => $fundraisingEmail->status,
                    'name' => $fundraisingEmail->name,
                    'subject' => $fundraisingEmail->subject,
                    'schedule_type' => $fundraisingEmail->schedule_type,
                    'schedule_days' => $fundraisingEmail->schedule_days,
                    'template' => $fundraisingEmail->template,
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
        FundraisingEmail::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
