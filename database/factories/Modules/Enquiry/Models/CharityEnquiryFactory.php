<?php

namespace Database\Factories\Modules\Enquiry\Models;

use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\CharityCategory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Enquiry\Models\CharityEnquiry>
 */
class CharityEnquiryFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'charity_category_id' => CharityCategory::factory(),
            'name' => $this->faker->name(),
            'registration_number' => $this->faker->randomNumber(5, true),
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
