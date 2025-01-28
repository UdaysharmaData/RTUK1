<?php

namespace Database\Factories\Modules\Event\Models;

use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Modules\Event\Models\EventPage;
use App\Modules\Charity\Models\Charity;
use App\Enums\EventPagePaymentOptionEnum;
use App\Modules\Corporate\Models\Corporate;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventPage>
 */
class EventPageFactory extends CustomFactory
{
    /**
     * Ensure only one event type (partner_event, virtual_event, rolling_event, rankings_event) is set.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (EventPage $eventPage) {
            if ($eventPage->charity_id) {
                $eventPage->corporate_id = null;
            } else {
                $eventPage->charity_id = null;
                $eventPage->corporate_id = Corporate::factory()->create()->id;
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
        return [
            // 'event_id' => Event::factory(),
            'charity_id' => $this->faker->randomElement([Charity::factory(), null]),
            'corporate_id' => $this->faker->randomElement([Corporate::factory(), null]),
            'slug' => $this->faker->unique()->slug(),
            'hide_helper' => $this->faker->boolean(),
            'fundraising_title' => $this->faker->randomElement([$this->faker->title(), null]),
            'fundraising_description' => $this->faker->randomElement([$this->faker->text(), null]),
            'fundraising_target' => $this->faker->randomNumber(4),
            'published' => $this->faker->boolean(95),
            'code' => $this->faker->randomNumber(4),
            'all_events' => $this->faker->boolean(),
            'fundraising_type' => $this->faker->randomElement([$this->faker->title(), null]),
            'black_text' => $this->faker->boolean(),
            'hide_event_description' => $this->faker->boolean(),
            'reg_form_only' => $this->faker->boolean(),
            'video' => $this->faker->url(),
            'use_enquiry_form' => $this->faker->boolean(),
            'payment_option' => $this->faker->randomElement(EventPagePaymentOptionEnum::cases()),
            'registration_price' => $this->faker->randomNumber(3),
            'registration_deadline' => $this->faker->dateTime()
        ];
    }
}
