<?php

namespace Database\Factories\Modules\Charity\Models;

use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;
use App\Modules\Corporate\Models\Corporate;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\Donation>
 */
class DonationFactory extends CustomFactory
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
            'corporate_id' => $this->faker->randomElement([Corporate::factory(), null]),
            'amount' => $this->faker->randomFloat(2, 0, 1000000),
            'conversion_rate' => $this->faker->randomElement([$this->faker->randomDigit(), $this->faker->randomFloat(2, 0, 10)]),
            'expires_at' => $this->faker->dateTimeBetween('-10 years', '+3 years')
        ];
    }
}