<?php

namespace Database\Factories\Modules\Charity\Models;

use App\Modules\Setting\Models\Site;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\CharityProfile>
 */
class CharityProfileFactory extends CustomFactory
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
            'site_id' => Site::factory(),
            'description' => $this->faker->text(),
            'mission_values' => $this->faker->text(),
            'video' => $this->faker->url()
        ];
    }
}
