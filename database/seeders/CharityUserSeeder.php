<?php

namespace Database\Seeders;

use DB;
use Schema;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\User;
use App\Enums\CharityUserTypeEnum;
use Illuminate\Support\Facades\Log;
use App\Modules\Charity\Models\Charity;
use App\Modules\User\Models\CharityUser;
use Database\Traits\EmptySpaceToDefaultData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CharityUserSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The charity user (charity participants) seeder logs');

        // $this->truncateTable();

        // This table keeps all the participants belonging to a charity

        $charityUsers = DB::connection('mysql_2')->table('charity_users')->get();
        
        foreach ($charityUsers as $charityUser) {
            $user = User::find($charityUser->user_id);
            $charity = Charity::find($charityUser->charity_id);

            $_user = $user ?? User::factory()->create(['id' => $charityUser->user_id]);
            $_charity = $charity ?? Charity::factory()->create(['id' => $charityUser->charity_id]);

            CharityUser::factory()
                ->for($_user)
                ->for($_charity)
                ->create([
                    'type' => CharityUserTypeEnum::Participant
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

        Schema::enableForeignKeyConstraints();
    }

}
