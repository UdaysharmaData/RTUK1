<?php

namespace Database\Seeders;

use App\Models\Passport\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    // use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->preSeedOperations();

        $this->call([
            SiteSeeder::class,
            ApiClientSeeder::class,
            RoleSeeder::class,
             // SiteUserSeeder::class,
            PermissionSeeder::class,
            PermissionRoleSeeder::class,
            // // LocationSeeder::class,
            TwoFactorAuthMethodSeeder::class,
            UserSeeder::class,
            EventManagerSeeder::class,
            CharityCategorySeeder::class,
            CharitySeeder::class,
            CharityUserSeeder::class,
            CharityProfileSeeder::class,
            ExperienceSeeder::class,
            EventCategorySeeder::class,
            PartnerSeeder::class,
            EventSeeder::class,
            CharityEventSeeder::class,
            // // EventEventCategorySeeder::class,
            EventEventManagerSeeder::class,
            CallNoteSeeder::class,
            CampaignSeeder::class,
            // // CampaignEventSeeder::class,
            CampaignLeadSeeder::class,
            DonationSeeder::class,

            EnquirySeeder::class,

            CharityEnquirySeeder::class,
            ExternalEnquirySeeder::class,
            PartnerEnquirySeeder::class,
            FundraisingEmailSeeder::class,
            CharityFundraisingEmailSeeder::class,
            // CharityFundraisingEmailEventSeeder::class,

            ParticipantSeeder::class,

            PartnerPackageSeeder::class,
            CharityPartnerPackageSeeder::class,
            ResalePlaceSeeder::class,
            ResaleRequestSeeder::class,
            ResaleNotificationSeeder::class,
            InvoiceSeeder::class,

            CharityListingSeeder::class,
            // // CharityCharityListingSeeder::class,
            CharityListingAdSeeder::class,

            EventEnquirySeeder::class,
            EventCustomFieldSeeder::class,
            ParticipantCustomFieldSeeder::class,

            EventPageSeeder::class,

            EventPlaceInvoiceSeeder::class,
            EventPageListingSeeder::class,
            // EventPageEventPageListingSeeder::class,
            EventCategoryEventPageListingSeeder::class,
            // EventPageEventCategoryEventPageListingSeeder::class,
            ListingPageSeeder::class,
            ListingPageCharitySeeder::class,

            PromotionalPageSeeder::class,
            PromotionalFeaturedEventSeeder::class,
            EventCategoryPromotionalEventSeeder::class,
            EventCategoryPromotionalEventSeeder::class,

            NationalAverageSeeder::class,
//            FaqSeeder::class,
            ApiClientCareerSeeder::class,
            TeammateSeeder::class,
        ]);

        // \App\Modules\User\Models\Permission::factory()->count(10)->create();
        // \App\Modules\Location\Models\Location::factory()->count(10)->create();
        // \App\Modules\Setting\Models\Site::factory()->count(10)->create();
        // \App\Modules\Charity\Models\CharityCategory::factory()->count(10)->create();
        // \App\Modules\Charity\Models\Charity::factory()->count(10)->create();
        // \App\Modules\Charity\Models\CharityMembership::factory()->count(10)->create();
        // \App\Modules\Charity\Models\CharityProfile::factory()->count(10)->create();
        // \App\Modules\Charity\Models\CharityEvent::factory()->count(10)->create();
        // \App\Modules\Charity\Models\CallNote::factory()->count(10)->create();
        // \App\Modules\Charity\Models\Campaign::factory()->count(10)->create();
        // \App\Modules\Charity\Models\CampaignLead::factory()->count(10)->create();
        // \App\Modules\Charity\Models\Donation::factory()->count(10)->create();
        // \App\Modules\Charity\Models\Enquiry::factory()->count(10)->create();
        // \App\Modules\Charity\Models\CharityEnquiry::factory()->count(10)->create();
        // \App\Modules\Charity\Models\ExternalEnquiry::factory()->count(10)->create()->each(function($enquiry) {
            // $enquiry->timeline = $enquiry->created_at;
            // $enquiry->save();
        // });
        // \App\Modules\Charity\Models\FundraisingEmail::factory()->count(10)->create();
        // \App\Modules\Charity\Models\CharityFundraisingEmail::factory()->count(10)->create();
        // \App\Modules\Charity\Models\CharityFundraisingEmailEvent::factory()->count(10)->create();

        // \App\Modules\Participant\Models\Participant::factory()->count(10)->create();

        // \App\Modules\Partner\Models\Partner::factory()->count(10)->create();
        // \App\Modules\Charity\Models\PartnerPackage::factory()->count(10)->create();
        // \App\Modules\Charity\Models\CharityPartnerPackage::factory()->count(10)->create();
        // \App\Models\Invoice::factory()->count(10)->create();
        // \App\Modules\Charity\Models\ResalePlace::factory()->count(10)->create();
        // \App\Modules\Charity\Models\ResaleRequest::factory()->count(10)->create();
        // \App\Modules\Charity\Models\ResaleNotification::factory()->count(10)->create();

        // \App\Modules\Charity\Models\CharityListing::factory()->count(10)->create();
        // \App\Modules\Charity\Models\CharityCharityListing::factory()->count(10)->create();
        // \App\Modules\Charity\Models\CharityListingAd::factory()->count(10)->create();

        // \App\Models\Experience::factory()->count(10)->create();
        // \App\Modules\Event\Models\EventCategory::factory()->count(10)->create();
        // \App\Modules\Event\Models\Event::factory()->count(10)->create();
        // // \App\Modules\Event\Models\EventEventCategory::factory()->count(10)->create();
        // // \App\Modules\Event\Models\EventLocation::factory()->count(10)->create();
        // \App\Modules\Event\Models\EventEventManager::factory()->count(10)->create();
        // \App\Modules\Event\Models\EventEnquiry::factory()->count(10)->create();
        // \App\Modules\Event\Models\EventPage::factory()->count(10)->create();
        // \App\Modules\Event\Models\EventEventPage::factory()->count(10)->create();
        // \App\Modules\Event\Models\EventPlaceInvoice::factory()->count(10)->create();
        // \App\Modules\Event\Models\EventPageListing::factory()->count(10)->create();
        // \App\Modules\Event\Models\EventPageEventPageListing::factory()->count(10)->create();
        // \App\Modules\Event\Models\EventCategoryEventPageListing::factory()->count(10)->create();
        // \App\Modules\Event\Models\EventPageEventCategoryEventPageListing::factory()->count(10)->create();
        // \App\Modules\Event\Models\ListingPage::factory()->count(10)->create();
        // \App\Modules\Event\Models\ListingPageCharity::factory()->count(10)->create();

        // \App\Modules\Event\Models\PromotionalPage::factory()->count(10)->create();
        // \App\Modules\Event\Models\PromotionalFeaturedEvent::factory()->count(10)->create();
        // \App\Modules\Event\Models\EventCategoryPromotionalEvent::factory()->count(10)->create();

        // \App\Modules\Event\Models\EventManager::factory()->count(10)->create();

        // \App\Modules\User\Models\User::factory()->count(10)->create();
        // \App\Modules\User\Models\CharityUser::factory()->count(10)->create();

        // \App\Modules\Partner\Models\PartnerEnquiry::factory()->count(10)->create();

        // \App\Modules\Event\Models\NationalAverage::factory()->count(10)->create();

        // \App\Models\User::factory(10)->create();
    }

    /**
     * add list of
     * @return void
     */
    private function preSeedOperations(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');

        // Delete these files
        if (File::exists('storage/logs/dataimport.log')) File::delete('storage/logs/dataimport.log');
        if (File::exists('storage/app/public/uploads')) File::deleteDirectory('storage/app/public/uploads');
        if (File::exists('storage/app/private/uploads')) File::deleteDirectory('storage/app/private/uploads');

        $path = storage_path('app/public/uploads/media/images');
        File::makeDirectory($path, 0777, true, true);

        if (File::exists(public_path('images/default-avatar.png')))
            File::copy(public_path('images/default-avatar.png'), storage_path('app/public/uploads/media/images/default-avatar.png')); // TODO: Check why this code does not create the directories /uploads/media/images but instead throws an error on some environments(servers)

        $this->createTestOauthClient();
    }

    /**
     * @return void
     */
    private function createTestOauthClient()
    {
        Client::create([
            'id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
            'name' => 'RunThrough',
            'redirect' => 'http://localhost',
            'personal_access_client' => true,
            'password_client' => false,
            'user_id' => null,
            'revoked' => false,
            'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
            'provider' => null
        ]);
    }
}
