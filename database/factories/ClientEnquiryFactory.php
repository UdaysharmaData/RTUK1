<?php

namespace Database\Factories;

use Database\Traits\SiteTrait;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Database\Factories\Factory<\App\Models\ClientEnquiry>
 */
class ClientEnquiryFactory extends Factory
{
    use SiteTrait;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'site_id' => static::getSite()?->id,
            'full_name' => $this->faker->unique()->name(),
            'email' => $this->faker->unique()->email(),
            'enquiry_type' => $this->faker->word(),
            'message' => $this->faker->text()
        ];
    }
}
