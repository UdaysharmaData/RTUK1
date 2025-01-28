<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Enums\RoleNameEnum;
use Database\Traits\SiteTrait;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Profile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeploymentEnquirySeeder extends Seeder
{
    use /*WithoutModelEvents,*/ SiteTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Modules\Enquiry\Models\Enquiry::factory()->count(100000)->create();
        \App\Modules\Enquiry\Models\EventEnquiry::factory()->count(100000)->create();
        \App\Modules\Enquiry\Models\CharityEnquiry::factory()->count(100000)->create();
        \App\Modules\Enquiry\Models\PartnerEnquiry::factory()->count(100000)->create();
        \App\Modules\Enquiry\Models\ExternalEnquiry::factory()->count(100000)->create();
        // \App\Models\ClientEnquiry::factory()->count(100000)->create();
    }
}
