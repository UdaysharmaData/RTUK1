<?php

namespace Database\Seeders;

Use DB;
use Schema;
use Illuminate\Support\Str;
use App\Models\ApiClient;
use App\Enums\RoleNameEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use App\Modules\Setting\Models\Setting;
use App\Enums\SiteUserStatus;
use Database\Traits\EmptySpaceToDefaultData;
use App\Modules\Setting\Models\SettingCustomField;
use App\Modules\User\Models\ActiveRole;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\RoleUser;
use App\Modules\User\Models\SiteUser;
use App\Modules\User\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Cache;

class NewSiteSeeder extends Seeder
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
        Log::channel('dataimport')->debug('The new site seeder logs');

        try {
            DB::beginTransaction();
    
            $data['name'] = 'Leicestershire 10K';
            $data['host'] = 'leicestershire10k.com';
            $data['code'] = 'leicestershire10k';
            $data['is_active'] = 1;
            $data['ip'] = null;

            $site = Site::firstOrNew([
                'domain' => $data['host'],
            ]);

            $site->name = $data['name'];
            $site->code = $data['code'];
            $site->status = 1;

            if (!$site->key) {
                $site->key = Site::generateRandomString();
            }

            $site->save();

            $pid = getmypid();

            Cache::put('command-site-' . $pid,  $site, now()->addHour());

            $this->createApiClient($site, $data);
            $this->createSetting($site);
            $this->createRoleWithPermissions($site);

            RoleUser::updateOrCreate([ // Add administrator role to user
                'role_id' => Role::where('site_id', $site->id)->where('name', RoleNameEnum::Administrator->value)->value('id'),
                'user_id' => User::where('email', 'matt@runthrough.co.uk')->value('id')
            ]);

            ActiveRole::updateOrCreate([ // Add the user's active role for the current platform
                'role_id' => Role::where('site_id', $site->id)->where('name', RoleNameEnum::Administrator->value)->value('id'),
                'user_id' => User::where('email', 'matt@runthrough.co.uk')->value('id')
            ]);

            if ($user = User::where('email', 'matt@runthrough.co.uk')->first()) {
                SiteUser::updateOrCreate([ // Grant access the the site
                    'site_id' => $site->id,
                    'user_id' => $user->id
                ], [
                    'status' => SiteUserStatus::Active
                ]);
            } else {
                echo 'The user matt@runthrough.co.uk was not found!';
            }

            Cache::forget('command-site-' . $pid);

            DB::commit();

            echo $data['name'] . " site created successfully!";

            // Generate the API Client Key through the command client:generate-key {client} for each client. Then use the value returned for each in the header of your request for the X-Client-Key property. This X-Client-Key is used to identify the client making the request.
            // Add the name and domain of the site created to App\Modules\Setting\Enums\SiteEnum.
            // Add the name and code of the site created to App\Modules\Setting\Enums\SiteCodeEnum.
            // Add the mail server, sitemap and stripe setups for the given site in the .env file. Example
            // LEICESTERSHIRE10K_MAIL_MAILER=smtp
            // LEICESTERSHIRE10K_MAIL_HOST=sandbox.smtp.mailtrap.io
            // LEICESTERSHIRE10K_MAIL_PORT=2525
            // LEICESTERSHIRE10K_MAIL_USERNAME=489a725dc887da # mesmer@sports-techsolutions.com
            // LEICESTERSHIRE10K_MAIL_PASSWORD=1e6fc9183e6ea4
            // LEICESTERSHIRE10K_MAIL_ENCRYPTION=tls

            // LEICESTERSHIRE10K_SITEMAP_PATH=/Users/tsafackvoufoaudreymesmer/Desktop/Sitemap
            // LEICESTERSHIRE10K_SITEMAP_FRONTEND_PATH=/Users/tsafackvoufoaudreymesmer/Desktop/Sitemap/Sitemaps
            // LEICESTERSHIRE10K_SITEMAP_UPDATE_FREQUENCY=daily
            // LEICESTERSHIRE10K_SITEMAP_REGENERATE_FREQUENCY=monthly

            // LEICESTERSHIRE10K_STRIPE_SECRET_KEY=sk_test_51Owm3bP39uV7sIRQawwAnJLfQHFtIvccVcFdeYaRhSnD4H58976wMb5WHZwP1bfK1TyxlrGUwDQUZ1kpFL4OxVxy00QmTQl7iN
            // LEICESTERSHIRE10K_STRIPE_WEBHOOK_SECRET_PAYMENT_INTENT=whsec_dusYyK0fc4iQe4DKOKiQ5on35FewXU9t
            // LEICESTERSHIRE10K_STRIPE_WEBHOOK_SECRET_PAYMENT_METHOD=whsec_VawH8oZtCymJ7UHLxBRY23TXKEXa6z5g
            // LEICESTERSHIRE10K_STRIPE_WEBHOOK_SECRET_PAYMENT_LINK=whsec_QNNJy0jp4xCc4tWKyjmvdmZOXIKfqXAX
            // LEICESTERSHIRE10K_STRIPE_WEBHOOK_SECRET_CHARGE=whsec_zTDDr2Dst2l5jlrjkbQNBk1u0gzD2bDL
            // Create a mail layout file using the code of the given site under resources/views/mails/layouts.
            // Create a config file using the code of the given site under config/mail.
            // Add the mail server configuration for the given site under the config/mail.php
            // Add the notification file for the given site under the config/notification
            // Add the stripe file for the given site under the config/stripe
            // Add the site to the env() method of the SiteEnum.

            // NB: Don't forget to check the file database/seeders/NewSiteSeeder.php to see the flow of most of these steps.

        } catch (\Exception $e) {
            DB::rollback();
            Log::channel('dataimport')->debug('An exception occured!' . $e);
            echo "An exception occured! \n \n";
            echo $e;
        }
    }

    /**
     * Create platform api client.
     *
     * @param  Site   $site
     * @param  array  $data
     * @return void
     */
    private function createApiClient(Site $site, array $data): void
    {
        ApiClient::updateOrCreate([
            'name' => $data['name']
        ],
        [
            'host' => $data['host'],
            'ip' => $data['ip'],
            'is_active' => $data['is_active'],
            'site_id' => $site->id
        ]);
    }

    /**
     * Create platform default setting.
     *
     * @param  Site $site
     * @return void
     */
    private function createSetting(Site $site): void
    {
        $setting = Setting::updateOrCreate([
            'site_id' => $site->id
        ]);
/*
        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'classic_membership_default_places'
        ], [
            'value' => 5,
            'type' => SettingCustomFieldTypeEnum::PerEvent
        ]);

        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'premium_membership_default_places',
        ], [
            'value' => 20,
            'type' => SettingCustomFieldTypeEnum::PerEvent
        ]);

        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'two_year_membership_default_places',
        ], [
            'value' => 20,
            'type' => SettingCustomFieldTypeEnum::PerEvent
        ]);

        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'partner_membership_default_places',
        ], [
            'value' => 1,
            'type' => SettingCustomFieldTypeEnum::AllEvents
        ]);

        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'classic_renewal',
        ], [
            'value' => 500
        ]);

        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'new_classic_renewal',
        ], [
            'value' => 750
        ]);

        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'premium_renewal',
        ], [
            'value' => 1000
        ]);

        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'new_premium_renewal',
        ], [
            'value' => 1200
        ]);

        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'two_year_renewal',
        ], [
            'value' => 1800
        ]);

        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'new_two_year_renewal',
        ], [
            'value' => 2500
        ]);

        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'partner_renewal',
        ], [
            'value' => 0
        ]);

        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'new_partner_renewal',
        ], [
            'value' => 0
        ]);
*/
        SettingCustomField::updateOrCreate([
            'setting_id' => $setting->id,
            'key' => 'participant_transfer_fee',
            'value' => 5
        ]);
    }

    /**
     * Create roles with permissions
     *
     * @param  Site  $site
     * @return void
     */
    private function createRoleWithPermissions(Site $site): void
    {
        foreach (Role::RoleWithDefaultPermissions as $key => $role) {
            if ($_role = RoleNameEnum::tryFrom($key)) {
                $__role = $site->roles()->updateOrCreate([
                    'name' => $key
                ], [
                    'description' => $_role->name
                ]);

                $permissions = []; // Set the list of permissions to be considered

                if (count($permissions)) {
                    $collection = collect($role);
                    $role = $collection->intersect($permissions);
                }

                $__permissions = [];

                foreach ($role as $permission) {
                    $_permission = $site->permissions()->updateOrCreate([
                        'name' => $permission
                    ], [
                        'description' => 'Allow the user to manage '. Str::replace('_', ' ', Str::substr($permission, strrpos($permission, 'manage_')))
                    ]);

                    $__permissions[] = $_permission->id;
                }

                $__role->permissions()->sync($__permissions);
            } else {
                \Log::debug('role' . $role . ' not found!');
            }
        }
    }
}
