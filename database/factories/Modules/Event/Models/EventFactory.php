<?php

namespace Database\Factories\Modules\Event\Models;

use Str;
use File;
use Carbon\Carbon;
use App\Models\City;
use App\Models\Venue;
use App\Models\Region;
use Database\Traits\SiteTrait;
use App\Models\Experience;
use App\Enums\EventTypeEnum;
use App\Models\EventExperience;
use App\Enums\EventReminderEnum;
use App\Enums\EventCharitiesEnum;
use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\Event>
 */
class EventFactory extends CustomFactory
{
    use SiteTrait;

    /**
     * Create the event experiences
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Event $event) {
            for ($i=0; $i<random_int(1, 4); $i++) {
                $experience = Experience::inRandomOrder()->first();

                EventExperience::factory()
                    ->for($event)
                    ->for($experience ?? Experience::factory()->create())
                    ->create();
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = $this->faker->unique()->name();
        $slug = Str::slug($name);
        $time = $this->faker->time('H:i');
        $custom_preferred_heat_time_start = $time.' - '.Carbon::parse($time)->addMinutes(rand(15, 30))->format('h:i');
        $time2 = Carbon::parse($time)->addHours(rand(2, 6));
        $custom_preferred_heat_time_end = $time2->format('h:i').' - '.Carbon::parse($time2)->addMinutes(rand(15, 30))->format('h:i');

        // $counties = ['midlands', 'overseas', 'south_west', 'south_east', 'south', 'london', 'north_west', 'scotland', 'ireland', 'wales', 'east_of_england', 'north_east', 'yorkshire', 'england_-_east', 'england_-_south', 'england_-_west', 'england_-_north', 'virtual'];

        return [
            'region_id' => Region::factory(),
            'venue_id' => Venue::factory(),
            'city_id' => City::factory(),
            'status' => $this->faker->boolean(),
            'name' => $name,
            'slug' => $slug,
            'postcode' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'description' => $this->faker->text(),
            'website' => $this->faker->url(),
            'video' => $this->faker->url(),
            'review' => $this->faker->url(),
            'estimated' => $this->faker->boolean(),
            'reg_preferred_heat_time' => $this->faker->boolean(),
            'reg_raced_before' => $this->faker->boolean(),
            'reg_estimated_finish_time' => $this->faker->boolean(),
            'reg_tshirt_size' => $this->faker->boolean(),
            'reg_age_on_race_day' => $this->faker->boolean(),
            // 'reg_first_name' => $this->faker->boolean(),
            // 'reg_last_name' => $this->faker->boolean(),
            // 'reg_email' => $this->faker->boolean(),
            // 'reg_gender' => $this->faker->boolean(),
            // 'reg_dob' => $this->faker->boolean(),
            'reg_month_born_in' => $this->faker->boolean(),
            'reg_nationality' => $this->faker->boolean(),
            'reg_occupation' => $this->faker->boolean(),
            'reg_address' => $this->faker->boolean(),
            'reg_city' => $this->faker->boolean(),
            'reg_region' => $this->faker->boolean(),
            'reg_postcode' => $this->faker->boolean(),
            'reg_country' => $this->faker->boolean(),
            // 'reg_phone' => $this->faker->boolean(),
            'reg_emergency_contact_name' => $this->faker->boolean(),
            'reg_emergency_contact_phone' => $this->faker->boolean(),
            'reg_minimum_age' => $this->faker->randomNumber(2),
            'reg_family_registrations' => $this->faker->boolean(),
            'reg_passport_number' => $this->faker->boolean(),
            'born_before' => $this->faker->date(),
            'custom_preferred_heat_time_start' => $custom_preferred_heat_time_start,
            'custom_preferred_heat_time_end' => $custom_preferred_heat_time_end,
            'terms_and_conditions' => $this->faker->url(),
            'charity_checkout_event_page_id' => $this->faker->randomElement([null, null, rand(1, 5000)]),
            'charity_checkout_event_page_url' => $this->faker->url(),
            'charity_checkout_raised' => $this->faker->randomNumber(6),
            'charity_checkout_title' => $this->faker->word(),
            'charity_checkout_status' => $this->faker->boolean(),
            'charity_checkout_integration' => $this->faker->boolean(90),
            'charity_checkout_created_at' => $this->faker->datetime(),
            'fundraising_emails' => $this->faker->boolean(10),
            'resale_price' => $this->faker->randomNumber(2),
            'reminder' => $this->faker->randomElement(EventReminderEnum::cases()),
            'type' => $this->faker->randomElement(EventTypeEnum::cases()),
            'charities' => $this->faker->randomElement(EventCharitiesEnum::cases()),
            'exclude_charities' => $this->faker->boolean(10),
            'exclude_website' => $this->faker->boolean(10),
            'exclude_participants' => $this->faker->boolean(10),
            'archived' => $this->faker->boolean(10),
            'route_info_code' => $this->faker->word(),
            'route_info_description' => $this->faker->realTextBetween(100, 10000),
            'what_is_included_description' => $this->faker->realTextBetween(100, 10000),
            'how_to_get_there' => $this->faker->realTextBetween(100, 10000),
            'event_day_logistics' => $this->faker->realTextBetween(100, 10000),
            'spectator_info' => $this->faker->realTextBetween(100, 10000),
            'kit_list' => $this->faker->realTextBetween(100, 10000),
        ];
    }
}
