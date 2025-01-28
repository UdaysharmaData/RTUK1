<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Enums\RoleNameEnum;
use Database\Traits\SiteTrait;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Profile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeploymentOtherSeeder extends Seeder
{
    use /*WithoutModelEvents,*/ SiteTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\Location::factory()->count(100000)->create();
        \App\Models\Experience::factory()->count(100000)->create();
        \App\Models\Region::factory()->count(100000)->create();
        \App\Models\City::factory()->count(100000)->create();
        // \App\Models\Invoice::factory()->count(100000)->create();
        \App\Models\InvoiceItem::factory()->count(100000)->create();
        // \App\Models\Faq::factory()->count(100000)->create();

        \App\Models\FaqDetails::factory()->count(100000)->create();
    }
}
