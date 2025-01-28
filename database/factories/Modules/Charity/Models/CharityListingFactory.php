<?php

namespace Database\Factories\Modules\Charity\Models;

use Str;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\CharityListing>
 */
class CharityListingFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = $this->faker->unique()->name();
        $slug = Str::slug($name);

        return [
            'charity_id' => Charity::factory(),
            'title' => $name,
            'slug' => $slug,
            'description' => $this->faker->text(),
            'url' => $this->faker->url(),
            'video' => $this->faker->url(),
            'show_2_year_members' => $this->faker->boolean(50),
            'primary_color' => $this->faker->hexColor(),
            'secondary_color' => $this->faker->hexColor(),
            'charity_custom_title' => $this->faker->randomElement([null, null, 'Charity of the Year', 'Main Charity']),
            'primary_partner_charities_custom_title' => $this->faker->randomElement([null, null, 'Partner Charites', 'Primary Partner Charities']),
            'secondary_partner_charities_custom_title' => $this->faker->randomElement([null, null, 'Secondary Partner Charites', 'Other Partner Charities']),
        ];
    }
}
