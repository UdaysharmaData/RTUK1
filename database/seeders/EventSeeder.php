<?php

namespace Database\Seeders;

use DB;
use Str;
use File;
use Schema;
use Storage;
use App\Models\Faq;
use App\Models\Venue;
use App\Models\Region;
use App\Models\FaqDetails;
use App\Enums\EventTypeEnum;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use Database\Traits\SiteTrait;
use Illuminate\Database\Seeder;
use App\Models\EventExperience;
use App\Enums\EventReminderEnum;
use App\Enums\LocationUseAsEnum;
use App\Enums\EventCharitiesEnum;
use App\Enums\SocialPlatformEnum;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use App\Enums\CharityEventTypeEnum;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Charity\Models\CharityEvent;
use App\Modules\Event\Models\EventThirdParty;
use App\Modules\Partner\Models\PartnerChannel;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Event\Models\EventCategoryEventThirdParty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Database\Traits\SlugTrait;
use Database\Traits\FormatDate;
use Database\Traits\EmptySpaceToDefaultData;

class EventSeeder extends Seeder
{
    use FormatDate, EmptySpaceToDefaultData, SlugTrait, SiteTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncateTables();

        Log::channel('dataimport')->debug('The event seeder logs');

        $events = DB::connection('mysql_2')->table('charity_events')->get();

        foreach ($events as $event) {
            $foreignKeyColumns = [];

            $_event = Event::Factory();

            if ($this->valueOrDefault($event->county)) { // check if the region exists
                $regionName = ucwords(Str::replace('_', ' ', Str::replace('_-_', ' ', $event->county)));

                $region = Region::where('name', $regionName)
                    ->where('site_id', static::getSite()?->id)
                    ->first();

                $_event = $_event->for($region ?? Region::factory()->create(['name' => $regionName]));
            } else {
                $foreignKeyColumns = ['region_id' => null];
            }

            if ($this->valueOrDefault($event->venue)) { // check if the venue exists
                $venue = Venue::where('name', $event->venue)
                    ->where('site_id', static::getSite()?->id)
                    ->first();

                $_event = $_event->for($venue ?? Venue::factory()->create(['name' => $event->venue]));
            } else {
                $foreignKeyColumns = ['venue_id' => null];
            }

            // Get the default classic and premium membership places based on the website
            $category = DB::connection('mysql_2')->table('event_categories')->where('id', $event->category_id)->first();

            $_event = $_event->create([
                ...$foreignKeyColumns,
                'id' => $event->id,
                'status' => $event->status,
                'name' => $event->title,
                'slug' => $this->getUniqueSlug(Event::class, $this->valueOrDefault($event->url, Str::slug($event->title))),
                'city_id' => null,
                'postcode' => null,
                'country' => $event->country,
                'description' => $event->description,
                'video' => $this->valueOrDefault($event->video),
                'website' => $event->website,
                'review' => $this->valueOrDefault($event->review),
                'estimated' => $event->estimated,
                'reg_preferred_heat_time' => $event->reg_preferred_heat_time,
                'reg_raced_before' => $event->reg_participant_raced_before,
                'reg_estimated_finish_time' => $event->reg_estimated_finish_time,
                'reg_tshirt_size' => $event->reg_tshirt_size,
                'reg_age_on_race_day' => $event->reg_age_on_race_day,
                // 'reg_gender' => $event->reg_gender,
                // 'reg_dob' => $event->reg_dob,
                'reg_month_born_in' => $event->reg_month_born_in,
                'reg_nationality' => $event->reg_nationality,
                'reg_occupation' => $event->reg_occupation,
                'reg_address' => $this->valueOrDefault($event->reg_address_1, 0) ? $event->reg_address_1 : $this->valueOrDefault($event->reg_address_2, 0),
                'reg_city' => $event->reg_city,
                'reg_region' => $event->reg_county,
                'reg_postcode' => $event->reg_postcode,
                'reg_country' => $event->reg_country,
                // 'reg_phone' => $event->reg_mobile,
                'reg_emergency_contact_name' => $event->reg_emergency_contact_name,
                'reg_emergency_contact_phone' => $event->reg_emergency_contact_telephone,
                'reg_minimum_age' => $this->valueOrDefault($event->reg_minimum_age),
                'born_before' => $this->dateOrNull(Str::replace('/', '-', $event->born_before)),
                'custom_preferred_heat_time_start' => $this->valueOrDefault($event->custom_preferred_heat_time_start),
                'custom_preferred_heat_time_end' => $this->valueOrDefault($event->custom_preferred_heat_time_end),
                'terms_and_conditions' => $event->terms_link,
                'charity_checkout_event_page_id' => $event->cc_event_page_id,
                'charity_checkout_event_page_url' => $event->cc_event_page_url,
                'charity_checkout_raised' => $event->cc_raised,
                'charity_checkout_title' => $event->cc_title,
                'charity_checkout_status' => $event->cc_status,
                'charity_checkout_integration' => !$event->cc_integration_disabled,
                'charity_checkout_created_at' => $event->cc_created_at,
                'fundraising_emails' => $event->drips_active,
                'resale_price' => $event->resale_price,
                'reminder' => $event->reminder != '' ? $event->reminder : EventReminderEnum::Monthly,
                'type' => $event->rolling_event ? EventTypeEnum::Rolling : EventTypeEnum::Standalone,
                'partner_event' => $event->partner,
                'charities' => $event->only_included_charities ? EventCharitiesEnum::Included : ($event->partner_disabled ? EventCharitiesEnum::Excluded : EventCharitiesEnum::All),
                'exclude_charities' => $event->exclude,
                'exclude_website' => $event->exclude_website,
                'exclude_participants' => $event->exclude_participants,
                'archived' => $event->archived,
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ]);

            if ($this->valueOrDefault($event->location)) { // check if the location exists
                $address = $_event->address()->updateOrCreate([
                    'use_as' => LocationUseAsEnum::Address
                ], [
                    'address' => $event->location
                ]);
            }

            // Save the image
            if ($this->valueOrDefault($event->image) && Storage::disk('sfc')->exists($event->image)) { // Copy the image from the sport-for-api disk if it exists
                $upload = $_event->uploads()->updateOrCreate([
                    'type' => UploadTypeEnum::Image,
                    'use_as' => UploadUseAsEnum::Image
                ], [
                    'title' => $_event->name,
                    'url' => config('app.images_path') . str_replace('uploads/', '', $event->image)
                ]);

                Storage::disk('public')->put($upload->real_path, Storage::disk('sfc')->get($event->image)); // Copy the image
            }

            if ($this->valueOrDefault($event->gallery)) { // save the event gallery (path)
                $galleries = DB::connection('mysql_2')->table('images')->whereIn('id', explode(',', $event->gallery))->get();

                foreach ($galleries as $gallery) {

                    if (Storage::disk('sfc')->exists($gallery->image)) { // Copy the image from the sport-for-api disk if it exists
                        $upload = $_event->uploads()->updateOrCreate([
                            'type' => UploadTypeEnum::Image,
                            'use_as' => UploadUseAsEnum::Gallery
                        ], [
                            'title' => $_event->name,
                            'url' => config('app.images_path') . str_replace('uploads/', '', $gallery->image)
                        ]);

                        Storage::disk('public')->put($upload->real_path, Storage::disk('sfc')->get($gallery->image)); // Copy the image
                    }
                }
            }

            if ($this->valueOrDefault($event->twitter)) { // save the twitter social
                $_event->socials()->updateOrCreate([
                    'platform' => SocialPlatformEnum::Twitter,
                ], [
                    'url' => $event->twitter
                ]);
            }

            // Link the event to it's category
            $eventCategory = EventCategory::find($event->category_id);
            $settings = DB::connection('mysql_2')->table('settings')->where('site_id', $category->site_id)->first();

            $eventEventCategory = EventEventCategory::factory()
                ->for($_event)
                ->for($eventCategory ?? EventCategory::factory()->create(['id' => $event->category_id]))
                ->create([
                    'local_fee' => $this->valueOrDefault($event->registration_price),
                    'international_fee' => $event->non_uk_registration_price,
                    'start_date' => $this->dateOrNow($event->start_date)->addYear()->toDateString(), // TODO: This year was added to make the events like for testing purposes. Remove it
                    'end_date' => $this->dateOrNow($event->end_date)->addYear()->toDateString(), // TODO: This year was added to make the events like for testing purposes. Remove it
                    'registration_deadline' => $this->dateOrNull($event->registration_deadline)?->addYear()->toDateString(), // TODO: This year was added to make the events like for testing purposes. Remove it
                    'withdrawal_deadline' => $this->dateOrNull($event->withdrawal_deadline)?->addYear()->toDateString(), // TODO: This year was added to make the events like for testing purposes. Remove it
                    'total_places' => $event->ticker,
                    'classic_membership_places' => $this->valueOrDefault((int) $event->classic_membership_places, $settings->classic_membership_default_places),
                    'premium_membership_places' => $this->valueOrDefault((int) $event->premium_membership_places, $settings->premium_membership_default_places),
                    'two_year_membership_places' => $this->valueOrDefault((int) $event->premium_membership_places, $settings->premium_membership_default_places)
                ]);

            $eventCategory = EventCategory::find($event->category_id); // the event category should have been created by now (from the block of code above)

            if ($this->valueOrDefault($event->partner_disabled)) { // Create the charities not allowed to run the event (partner_disabled).
                foreach (explode(',', $event->partner_disabled) as $charity_id) {
                    if ($this->valueOrDefault($charity_id)) {
                        $charity = Charity::find($charity_id);

                        CharityEvent::factory()
                            ->for($_event)
                            ->for($charity ?? Charity::factory()->create(['id' => $charity_id]))
                            ->create([
                                'type' => CharityEventTypeEnum::Excluded
                            ]);

                        if (!$charity) {
                            Log::channel('dataimport')->debug("id: {$event->id} The charity id  {$charity_id} did not exists and was created. Event: ".json_encode($event));
                        }
                    }
                }
            }

            $userId = User::inRandomOrder()->whereIn('email', [/*'matt@runthrough.co.uk',*/'Mark@runforcharity.com'])->value('id');

            for ($i=0; $i<5; $i++) { // Create 5 faq per event
                Faq::insert([
                    'ref' => Str::orderedUuid(),
                    'user_id' => $userId,
                    'faqsable_type' => Event::class,
                    'faqsable_id' => $event->id,
                    'section' => "Ut esse distinctio maxime occaecati debitis incidunt dolorem esse., Dignissimos consectetur explicabo perspiciatis aut beatae quam quis impedit dolorem error dolorem.",
                    'description' => "Ut esse distinctio maxime occaecati debitis incidunt dolorem esse., Dignissimos consectetur explicabo perspiciatis aut beatae quam quis impedit dolorem error dolorem."            
                ]);

                $faq = Faq::latest()->first();

                for ($j=0; $j<5; $j++) { // Create 5 faq details per faq
                    $fadDetails = FaqDetails::factory()
                        ->for($faq)
                        ->create();
                }
            }

            // Link the event to the LDT third party channel
            $etp = EventThirdParty::factory()
                ->for($_event)
                ->create(['partner_channel_id' => PartnerChannel::where('code', 'letsdothisbespoke')->value('id')]);

            EventCategoryEventThirdParty::factory()
                ->for($etp)
                ->for($eventEventCategory->eventCategory)
                ->create();

            if (!$eventCategory) {
                Log::channel('dataimport')->debug("id: {$event->id} The event category id  {$event->category_id} did not exists and was created. Event: ".json_encode($event));
            }
        }
    }

    /**
     * Truncate the tables
     *
     * @return void
     */
    public function truncateTables()
    {
        Schema::disableForeignKeyConstraints();
        Event::truncate();
        EventEventCategory::truncate();
        CharityEvent::truncate();
        EventExperience::truncate();
        Region::truncate();
        EventThirdParty::truncate();
        EventCategoryEventThirdParty::truncate();
        Schema::enableForeignKeyConstraints();
    }
}
