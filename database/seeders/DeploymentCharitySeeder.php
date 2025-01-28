<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Enums\RoleNameEnum;
use Database\Traits\SiteTrait;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Profile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeploymentCharitySeeder extends Seeder
{
    use /*WithoutModelEvents,*/ SiteTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Modules\Charity\Models\CharityCategory::factory()->count(100000)->create();
        \App\Modules\Charity\Models\Charity::factory()->count(100000)->create();
        \App\Modules\Charity\Models\CharityMembership::factory()->count(100000)->create();
        \App\Modules\Charity\Models\CharityProfile::factory()->count(100000)->create();
        \App\Modules\Charity\Models\CharityEvent::factory()->count(100000)->create();
        \App\Modules\Charity\Models\CallNote::factory()->count(100000)->create();
        \App\Modules\Charity\Models\Campaign::factory()->count(100000)->create();
        \App\Modules\Charity\Models\CampaignLead::factory()->count(100000)->create();
        \App\Modules\Charity\Models\Donation::factory()->count(100000)->create();
        \App\Modules\Charity\Models\CharityFundraisingEmail::factory()->count(100000)->create();
        \App\Modules\Charity\Models\CharityFundraisingEmailEvent::factory()->count(100000)->create();
        \App\Modules\Partner\Models\Partner::factory()->count(100000)->create();
        \App\Modules\Charity\Models\PartnerPackage::factory()->count(100000)->create();
        \App\Modules\Charity\Models\CharityPartnerPackage::factory()->count(100000)->create();
        \App\Modules\Charity\Models\ResalePlace::factory()->count(100000)->create();
        \App\Modules\Charity\Models\ResaleRequest::factory()->count(100000)->create();
        \App\Modules\Charity\Models\ResaleNotification::factory()->count(100000)->create();
        \App\Modules\Charity\Models\CharityListing::factory()->count(100000)->create();
        \App\Modules\Charity\Models\CharityCharityListing::factory()->count(100000)->create();
        \App\Modules\Charity\Models\CharityListingAd::factory()->count(100000)->create();
        \App\Modules\Event\Models\ListingPage::factory()->count(100000)->create();
        \App\Modules\Event\Models\ListingPageCharity::factory()->count(100000)->create();
        \App\Modules\Charity\Models\EventPlaceInvoice::factory()->count(100000)->create();
    }
}
