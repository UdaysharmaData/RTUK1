<?php

namespace Database\Seeders;

Use DB;
use Str;
use Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use App\Modules\Setting\Models\Setting;
use App\Enums\SettingCustomFieldTypeEnum;
use App\Enums\SettingCustomFieldKeyEnum;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Setting\Models\SettingCustomField;
use Hamcrest\Core\Set;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SiteSeeder extends Seeder
{
    use EmptySpaceToDefaultData;

    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The site seeder logs');

        $this->truncateTables();

        $sites = DB::connection('mysql_2')->table('sites')->get();

        foreach ($sites as $site) {
            $_site = Site::factory()
                ->create([
                    'id' => $site->id,
                    'domain' => $site->domain,
                    'key' => Site::generateRandomString(),
                    'name' => $site->name,
                    'code' => Str::replace('.com', '', $site->domain),
                    'status' => $site->status,
                ]);

            $setting = DB::connection('mysql_2')->table('settings')->where('site_id', $site->id)->first();

            if ($setting) { // Create the website settings
                $_setting = Setting::factory()
                    ->for($_site)
                    ->create();

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => SettingCustomFieldKeyEnum::ClassicMembershipDefaultPlaces,
                        'value' => $this->valueOrDefault($setting->classic_membership_default_places),
                        'type' => SettingCustomFieldTypeEnum::PerEvent
                    ]);

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => SettingCustomFieldKeyEnum::PremiumMembershipDefaultPlaces,
                        'value' => $this->valueOrDefault($setting->premium_membership_default_places),
                        'type' => SettingCustomFieldTypeEnum::PerEvent
                    ]);

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => SettingCustomFieldKeyEnum::TwoYearMembershipDefaultPlaces,
                        'value' => $this->valueOrDefault($setting->premium_membership_default_places),
                        'type' => SettingCustomFieldTypeEnum::PerEvent
                    ]);

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => SettingCustomFieldKeyEnum::PartnerMembershipDefaultPlaces,
                        'value' => 1,
                        'type' => SettingCustomFieldTypeEnum::AllEvents
                    ]);

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => SettingCustomFieldKeyEnum::ClassicRenewal,
                        'value' => $this->valueOrDefault($setting->classic_renewal)
                    ]);

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => SettingCustomFieldKeyEnum::NewClassicRenewal,
                        'value' => $this->valueOrDefault($setting->new_classic_renewal)
                    ]);

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => SettingCustomFieldKeyEnum::PremiumRenewal,
                        'value' => $this->valueOrDefault($setting->premium_renewal)
                    ]);

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => SettingCustomFieldKeyEnum::NewPremiumRenewal,
                        'value' => $this->valueOrDefault($setting->new_premium_renewal)
                    ]);

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => SettingCustomFieldKeyEnum::TwoYearRenewal,
                        'value' => $this->valueOrDefault($setting->two_year_renewal)
                    ]);

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => SettingCustomFieldKeyEnum::NewTwoYearRenewal,
                        'value' => $this->valueOrDefault($setting->new_two_year_renewal)
                    ]);

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => SettingCustomFieldKeyEnum::PartnerRenewal,
                        'value' => $this->valueOrDefault($setting->partner_renewal)
                    ]);

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => SettingCustomFieldKeyEnum::NewPartnerRenewal,
                        'value' => 0
                    ]);

                SettingCustomField::factory()
                    ->for($_setting)
                    ->create([
                        'key' => 'participant_transfer_fee',
                        'value' => 10
                    ]);
            } else {
                Log::channel('dataimport')->debug("id: {$site->id} The site setting did not exists and was created.");

                $this->createSetting($_site);
            }
        }

        // Create the Virtual Marathon Series site
        $_site = Site::factory()
            ->create([
                'domain' => 'runthrough.co.uk',
                'name' => 'RunThrough',
                'code' => 'runthrough',
                'key' => \App\Modules\Setting\Models\Site::generateRandomString(),
                'status' => Site::ACTIVE
            ]);

        $this->createSetting($_site);

        // Create the Virtual Marathon Series site
        $_site = Site::factory()
            ->create([
                'domain' => 'virtualmarathonseries.com',
                'name' => 'Virtual Marathon Series',
                'code' => 'vms',
                'key' => \App\Modules\Setting\Models\Site::generateRandomString(),
                'status' => Site::ACTIVE
            ]);

        $this->createSetting($_site);

        // Create the Runthrough hub site
        $_site = Site::factory()
            ->create([
                'domain' => 'hub.runthrough.co.uk',
                'name' => 'RunThrough Hub',
                'code' => 'rthub',
                'key' => \App\Modules\Setting\Models\Site::generateRandomString(),
                'status' => Site::ACTIVE
            ]);

        $this->createSetting($_site);

        // Create the General site (The site that manages all the sites)
        $_site = Site::factory()
            ->create([
                'domain' => 'sportsmediaagency.com',
                'name' => 'Sports Media Agency',
                'code' => 'sma',
                'key' => \App\Modules\Setting\Models\Site::generateRandomString(),
                'status' => Site::ACTIVE
            ]);

        $this->createSetting($_site);
    }

    /**
     * Truncates the table
     *
     * @return void
     */
    public function truncateTables()
    {
        Schema::disableForeignKeyConstraints();
        Site::truncate();
        Setting::truncate();
        SettingCustomField::truncate();
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Create website default setting.
     *
     * @param  Site $site
     * @return void
     */
    private function createSetting(Site $_site): void
    {
        $_setting = Setting::factory()
            ->for($_site)
            ->create();
        
        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::ParticipantTransferFee,
                'value' => 10,
                'type' => SettingCustomFieldTypeEnum::AllEvents
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::ClassicMembershipDefaultPlaces,
                'value' => 5,
                'type' => SettingCustomFieldTypeEnum::PerEvent
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::PremiumMembershipDefaultPlaces,
                'value' => 20,
                'type' => SettingCustomFieldTypeEnum::PerEvent
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::TwoYearMembershipDefaultPlaces,
                'value' => 20,
                'type' => SettingCustomFieldTypeEnum::PerEvent
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::PartnerMembershipDefaultPlaces,
                'value' => 1,
                'type' => SettingCustomFieldTypeEnum::AllEvents
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::ClassicRenewal,
                'value' => 500
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::NewClassicRenewal,
                'value' => 750
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::PremiumRenewal,
                'value' => 1000
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::NewPremiumRenewal,
                'value' => 1200
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::TwoYearRenewal,
                'value' => 1800
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::NewTwoYearRenewal,
                'value' => 2500
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::PartnerRenewal,
                'value' => 0
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => SettingCustomFieldKeyEnum::NewPartnerRenewal,
                'value' => 0
            ]);

        SettingCustomField::factory()
            ->for($_setting)
            ->create([
                'key' => 'participant_transfer_fee',
                'value' => 10
            ]);
    }
}
