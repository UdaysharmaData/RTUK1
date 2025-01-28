<?php

namespace Database\Factories\Modules\Charity\Models;

use Database\Factories\CustomFactory;
use App\Enums\CharityListingAdTypeEnum;
use App\Enums\CharityListingAdPositionEnum;
use App\Modules\Charity\Models\CharityListing;

/**
 * @extends \Database\Factories\CustomFactory<\App\Model>
 */
class CharityListingAdFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'charity_listing_id' => CharityListing::factory(),
            'key' => $this->faker->randomDigit(),
            'position' => $this->faker->randomElement(CharityListingAdPositionEnum::cases()),
            'type' => $this->faker->randomElement(CharityListingAdTypeEnum::cases()),
            'link' => $this->faker->url()
        ];
    }
}
