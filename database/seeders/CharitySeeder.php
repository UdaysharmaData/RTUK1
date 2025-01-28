<?php

namespace Database\Seeders;

Use DB;
Use Str;
Use File;
use Schema;
use Storage;
use Carbon\Carbon;
use App\Models\Location;
use App\Enums\RoleNameEnum;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Illuminate\Database\Seeder;
use App\Enums\LocationUseAsEnum;
use App\Enums\SocialPlatformEnum;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Role;
use App\Enums\CharityUserTypeEnum;
use Illuminate\Support\Facades\Log;
use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\Charity;
use App\Enums\CharityMembershipTypeEnum;
use App\Modules\User\Models\CharityUser;
use App\Modules\Charity\Models\CharityCategory;
use App\Modules\Charity\Models\CharityMembership;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Database\Traits\SlugTrait;
use Database\Traits\FormatDate;
use Database\Traits\EmptySpaceToDefaultData;

class CharitySeeder extends Seeder
{
    use FormatDate, EmptySpaceToDefaultData, SlugTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::channel('dataimport')->debug('The charity seeder logs');

        $this->truncateTables();

        $charities = DB::connection('mysql_2')->table('charities')->get();

        foreach ($charities as $charity) {
            $user = DB::connection('mysql_2')->table('users')->where('id', $charity->user_id)->first(); // get the user external enquiry notification settings from the old database
            $foreignKeyColumns = [];

            $external_enquiry_notification_settings = $this->valueOrDefault($user?->external_enquiry_notification_settings ?: 'each', 'each');

            $charityCategory = CharityCategory::find($charity->category_id);

            $_charity = Charity::factory();

            $_charity = $_charity->for($charityCategory ?? CharityCategory::factory()->create(['id' => $charity->category_id]))
                ->create([
                    ...$foreignKeyColumns,
                    'id' => $charity->id,
                    'name' => $charity->title,
                    'slug' => $this->getUniqueSlug(Charity::class, $this->valueOrDefault($charity->url, Str::slug($charity->title))),
                    'email' => $charity->email,
                    'phone' => $charity->phone,
                    'postcode' => $charity->postcode,
                    'city' => $charity->city,
                    'country' => $charity->country,
                    'primary_color' => $charity->color_header,
                    'secondary_color' => $charity->color2_header,
                    'website' => $charity->website,
                    'supporters_video' => $charity->supporters_video,
                    'donation_link' => $charity->link_donate != 0 ? $charity->link_donate : null,
                    'show_in_external_feeds' => $charity->show_in_external_feeds,
                    'show_in_vmm_external_feeds' => $charity->show_in_vmm_external_feeds,
                    'external_strapline' => $charity->external_strapline,
                    'charity_checkout_id' => $charity->cc_id,
                    'charity_checkout_integration' => !$charity->cc_integration_disabled, // save the opposite of the current value to reflect the new attribute name.
                    'fundraising_emails_active' => $charity->drips_active,
                    'complete_notifications' => $charity->complete_notifications,
                    'external_enquiry_notification_settings' => $external_enquiry_notification_settings,
                    'fundraising_platform' => $charity->fundraising_platform,
                    'fundraising_platform_url' => $charity->fundraising_platform_url,
                    'fundraising_ideas_url' => $charity->fundraising_ideas_url,
                    'finance_contact_name' => $charity->finance_contact_name,
                    'finance_contact_email' => $charity->finance_contact_email,
                    'finance_contact_phone' => $charity->finance_contact_phone,
                    'manager_call_notes' => $charity->manager_call_notes,
                    'manager_call_status' => $charity->manager_call_status,
                    'created_at' => $charity->created_at,
                    'updated_at' => $charity->updated_at
                ]);

            if ($this->valueOrDefault($charity->address)) { // check if the address exists
                $address = $_charity->location()->updateOrCreate([
                    'use_as' => LocationUseAsEnum::Address
                ], [
                    'address' => $charity->address
                ]);
            }

            // Save the image (logo) path
            if ($this->valueOrDefault($charity->image) && Storage::disk('sfc')->exists($charity->image)) { // Copy the image from the sport-for-api disk if it exists
                $upload = $_charity->uploads()->updateOrCreate([
                    'type' => UploadTypeEnum::Image,
                    'use_as' => UploadUseAsEnum::Logo
                ], [
                    'title' => $_charity->name,
                    'url' => config('app.images_path') . str_replace('uploads/', '', $charity->image),
                ]);

                Storage::disk('public')->put($upload->real_path, Storage::disk('sfc')->get($charity->image)); // Copy the image
            }

            $charityImages = DB::connection('mysql_2')->table('charity_images')->where('charity_id', $_charity->id)->get();

            if ($charityImages) { // save the charity images (path)
                foreach ($charityImages as $charityImage) {
                    if (Storage::disk('sfc')->exists($charityImage->image)) {  // Copy the image from the sport-for-api disk if it exists
                        $site = Site::find($charityImage->site_id);
                        $site_id = $site?->id ?: ($site ? Site::factory()->create(['id' => $charityImage->id])->id : null);

                        $upload = $_charity->uploads()->updateOrCreate([
                            'site_id' => $site_id,
                            'type' => UploadTypeEnum::Image,
                            'use_as' => UploadUseAsEnum::Images
                        ], [
                            'title' => $_charity->name,
                            'url' => config('app.images_path') . str_replace('uploads/', '', $charityImage->image),
                            'description' => $this->valueOrDefault($charityImage->text)
                        ]);

                        Storage::disk('local')->put('public'.$upload->real_path, Storage::disk('sfc')->get($charityImage->image));
                    }
                }
            }

            if ($charity->old_membership_type) { // Save previous membership type
                $type = $this->valueOrDefault($charity->old_membership_type, CharityMembershipTypeEnum::Classic->value);
                $startDate = $this->getMembershipStartDate($this->dateOrNow($charity->old_expiry_date), $type);

                CharityMembership::factory()
                    ->for($_charity)
                    ->create([
                        'type' => $type,
                        'status' => CharityMembership::INACTIVE,
                        'use_new_membership_fee' => 0,
                        'renewed_on' => Carbon::parse($startDate)->subDay(), // We assume the old membership was renewed on the start_date - 1
                        // 'start_date' => $startDate,
                        'expiry_date' => $this->dateOrNow($charity->old_expiry_date)
                    ]);
            }


            // Save current membership type
            // $type = $this->valueOrDefault($charity->membership_type, CharityMembershipTypeEnum::Classic->value);

            CharityMembership::factory()
                ->for($_charity)
                ->create([
                    'type' => $type,
                    'status' => CharityMembership::ACTIVE,
                    'use_new_membership_fee' => $charity->use_new_membership_fee,
                    'renewed_on' => $this->dateOrNow($charity->renewed_on),
                    // 'start_date' => $this->getMembershipStartDate($this->dateOrNow($charity->expiry_date), $type),
                    'expiry_date' => $this->dateOrNow($charity->expiry_date)
                ]);

            if (!$charityCategory) {
                Log::channel('dataimport')->debug("id: {$charity->id} The charity category id  {$charity->category_id} did not exists and was created. Charity: ".json_encode($charity));
            }

            $user = User::find($charity->user_id);

            if (!$user) { // Save the user (charity owner)
                $_user = User::factory()->create(['id' => $charity->user_id]);

                CharityUser::factory()
                    ->for($_charity)
                    ->for($_user)
                    ->create([
                        'type' => CharityUserTypeEnum::Owner
                    ]);

                Log::channel('dataimport')->debug("id: {$charity->id} The user id (charity owner) {$charity->user_id} did not exists and was created. Charity: ".json_encode($charity));

            } else { // check and create the CharityUser record
                if (!$user->charityUser) {
                    CharityUser::factory()
                        ->for($_charity)
                        ->for($user)
                        ->create([
                            'type' => CharityUserTypeEnum::Owner
                        ]);
                }
            }

            $user = User::find($charity->manager_id);

            if (!$user) { // Save the account manager (charity manager)
                $_user = User::factory()->create(['id' => $charity->manager_id]);

                CharityUser::factory()
                    ->for($_charity)
                    ->for($_user)
                    ->create([
                        'type' => CharityUserTypeEnum::Manager
                    ]);

                Log::channel('dataimport')->debug("id: {$charity->id} The user id (charity manager) {$charity->manager_id} did not exists and was created. Charity: ".json_encode($charity));

            } else { // check and create the CharityUser record
                if (!$user->charityUsers || !$user->charityUsers->where('charity_id', $_charity->id)->first()) {
                    CharityUser::factory()
                        ->for($_charity)
                        ->for($user)
                        ->create([
                            'type' => CharityUserTypeEnum::Manager
                        ]);
                }
            }

            // Get the charity users
            $charityUsers = DB::connection('mysql_2')->table('users')->where('charity_id', $_charity->id)->where('role_id', Role::where('name', RoleNameEnum::CharityUser)->first()?->id)->get();

            foreach ($charityUsers as $charityUser) { // A charity can have multiple charity users but just one charity owner.
                $user = User::find($charityUser->id);

                if (!$user) { // Save the user (charity user)
                    $_user = User::factory()->create(['id' => $charityUser->id]);

                    CharityUser::factory()
                        ->for($_charity)
                        ->for($_user)
                        ->create([
                            'type' => CharityUserTypeEnum::User
                        ]);

                    Log::channel('dataimport')->debug("id: {$charity->id} The user id (charity user) {$charityUser->id} did not exists and was created. Charity: ".json_encode($charity));

                } else { // check and create the CharityUser record
                    if (!$user->charityUser) {
                        CharityUser::factory()
                            ->for($_charity)
                            ->for($user)
                            ->create([
                                'type' => CharityUserTypeEnum::User
                            ]);
                    }
                }
            }

            if ($charity->social_facebook && $charity->social_facebook != 0) { // Save the facebook social
                $_charity->socials()->updateOrCreate([
                    'platform' => SocialPlatformEnum::Facebook,
                ], [
                    'url' => $charity->social_facebook
                ]);
            }

            if ($charity->social_twitter && $charity->social_twitter != 0) { // Save the twitter social
                $_charity->socials()->updateOrCreate([
                    'platform' => SocialPlatformEnum::Twitter,
                ], [
                    'url' => $charity->social_twitter
                ]);
            }

            if ($charity->social_instagram && $charity->social_instagram != 0) { // Save the instagram social
                $_charity->socials()->updateOrCreate([
                    'platform' => SocialPlatformEnum::Instagram,
                ], [
                    'url' => $charity->social_instagram
                ]);
            }
        }
    }

    /**
     * Get the start_date of a charity's membership from it's membership_type and expiry_date
     *
     * @param  Carbon   $expiryDate
     * @param  string $type
     * @return mixed
     */
    private function getMembershipStartDate(Carbon $expiryDate, string $type): mixed
    {
        $startDate = Carbon::parse($expiryDate);

        switch ($type) {
            case CharityMembershipTypeEnum::Classic->value:
            case CharityMembershipTypeEnum::Premium->value:
            case CharityMembershipTypeEnum::Partner->value:
                $startDate = $startDate->subYear();
                break;

            case CharityMembershipTypeEnum::TwoYear->value:
                $startDate = $startDate->subYears(2);
                break;
        }

        if (! $startDate->isValid() || $startDate->year < 0) { // This check is necessary because some dates have the value 0001-11-30 00:00:00 which when applied subYear(2) returns -0001-11-30 which is invalid
            $startDate = $expiryDate; // Revert to the previous value (valid date)
        }

        return $startDate;
    }

    /**
     * Truncate the tables
     *
     * @return void
     */
    public function truncateTables()
    {
        Schema::disableForeignKeyConstraints();
        Charity::truncate();
        Location::truncate();
        CharityUser::truncate();
        CharityMembership::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
