<?php

namespace Database\Factories\Modules\Charity\Models;

use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\FundraisingEmail;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\CharityFundraisingEmail>
 */
class CharityFundraisingEmailFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'charity_id' => Charity::factory(),
            'fundraising_email_id' => FundraisingEmail::factory(),
            'status' => $this->faker->boolean(),
            'content' => $this->faker->text(),
            'from_name' => $this->faker->name(),
            'from_email' => $this->faker->safeEmail(),
            'all_events' => $this->faker->boolean(5)
        ];
    }
}
