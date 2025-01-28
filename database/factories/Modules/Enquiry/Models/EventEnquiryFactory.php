<?php

namespace Database\Factories\Modules\Enquiry\Models;

use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Enquiry\Models\EventEnquiry>
 */
class EventEnquiryFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'distance' => $this->faker->randomElement(['2km', '10k', '5k', 'Half Marathon', '230miles', '500k', '8km & 16km routes available']),
            'entrants' => $this->faker->randomNumber(5),
            'website' => $this->faker->url(),
            'address_1' => $this->faker->address(),
            'address_2' => $this->faker->address(),
            'city' => $this->faker->city(),
            'postcode' => $this->faker->postcode(),
            'contact_name' => $this->faker->name(),
            'contact_email' => $this->faker->safeEmail(),
            'contact_phone' => $this->faker->phoneNumber()
        ];
    }
}
