<?php

namespace Database\Factories\Modules\Enquiry\Models;

use App\Enums\GenderEnum;
use App\Enums\EnquiryStatusEnum;
use App\Modules\Event\Models\Event;
use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\Charity;
use App\Modules\Corporate\Models\Corporate;
use App\Modules\Participant\Models\Participant;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Enquiry\Models\Enquiry>
 */
class EnquiryFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'charity_id' => $this->faker->randomElement([Charity::factory(), null]),
            'participant_id' => $this->faker->randomElement([Participant::factory(), null]),
            // 'event_id' => $this->faker->randomElement([Event::factory(), null]),
            // 'corporate_id' => $this->faker->randomElement([Corporate::factory(), null]),
            'site_id' => $this->faker->randomElement([Site::factory(), null]),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'gender' => $this->faker->randomElement(GenderEnum::cases()),
            'postcode' => $this->faker->postcode(),
            'contacted' => $this->faker->boolean(70),
            'converted' => $this->faker->boolean(70),
            'comments' => $this->faker->sentence(),
            'custom_charity' => $this->faker->randomElement([$this->faker->name(), null, null, null]),
            'fundraising_target' => $this->faker->randomElement([$this->faker->randomFloat(2, 0, 1000000), null])
        ];
    }
}
