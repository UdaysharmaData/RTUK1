<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Enums\RoleNameEnum;
use Database\Traits\SiteTrait;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Profile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeploymentUserSeeder extends Seeder
{
    use /*WithoutModelEvents,*/ SiteTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Modules\User\Models\User::factory()->count(100000)->create();
        \App\Modules\User\Models\Profile::factory()->count(100000)->create();
        \App\Modules\User\Models\ParticipantProfile::factory()->count(100000)->create();
        \App\Modules\User\Models\CharityUser::factory()->count(100000)->create();
    }
}
