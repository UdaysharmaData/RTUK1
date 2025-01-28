<?php

namespace Database\Seeders;

use Database\Traits\SiteTrait;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeploymentNewSeeder extends Seeder
{
    use /*WithoutModelEvents,*/ SiteTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\City::factory()->count(1000)->create();
        \App\Models\Venue::factory()->count(1000)->create();
        \App\Models\Region::factory()->count(1000)->create();
        \App\Models\Medal::factory()->count(1000)->create();
        \App\Models\Page::factory()->count(1000)->create();
        \App\Models\Location::factory()->count(500)->create();
        \App\Models\EventExperience::factory()->count(1000)->create();
        \App\Models\Combination::factory()->count(1000)->create();
        \App\Models\InvoiceItem::factory()->count(1000)->create();
        \App\Models\FaqDetails::factory()->count(1000)->create();
        \App\Modules\Event\Models\Serie::factory()->count(1000)->create();
        \App\Modules\Event\Models\Sponsor::factory()->count(1000)->create();
        \App\Modules\Charity\Models\Charity::factory()->count(1000)->create();
        \App\Modules\Enquiry\Models\Enquiry::factory()->count(1000)->create();
        \App\Models\ClientEnquiry::factory()->count(1000)->create();
        \App\Modules\Enquiry\Models\CharityEnquiry::factory()->count(1000)->create();


    }
}
