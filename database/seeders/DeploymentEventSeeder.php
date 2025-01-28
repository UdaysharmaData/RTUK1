<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Enums\RoleNameEnum;
use Database\Traits\SiteTrait;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Profile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeploymentEventSeeder extends Seeder
{
    use /*WithoutModelEvents,*/ SiteTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Modules\Event\Models\EventCategory::factory()->count(100000)->create();
        \App\Modules\Event\Models\Event::factory()->count(100000)->create();
        \App\Modules\Event\Models\EventEventCategory::factory()->count(100000)->create();
        \App\Modules\Event\Models\EventEventManager::factory()->count(100000)->create();
        \App\Modules\Event\Models\EventPage::factory()->count(100000)->create();
        \App\Modules\Event\Models\EventEventPage::factory()->count(100000)->create();
        \App\Modules\Event\Models\EventPageListing::factory()->count(100000)->create();
        \App\Modules\Event\Models\EventPageEventPageListing::factory()->count(100000)->create();
        \App\Modules\Event\Models\EventCategoryEventPageListing::factory()->count(100000)->create();
        \App\Modules\Event\Models\EventPageEventCategoryEventPageListing::factory()->count(100000)->create();
        \App\Modules\Event\Models\PromotionalPage::factory()->count(100000)->create();
        \App\Modules\Event\Models\PromotionalFeaturedEvent::factory()->count(100000)->create();
        \App\Modules\Event\Models\EventCategoryPromotionalEvent::factory()->count(100000)->create();
        \App\Modules\Event\Models\EventManager::factory()->count(100000)->create();
        \App\Modules\Event\Models\NationalAverage::factory()->count(100000)->create();
    }
}
