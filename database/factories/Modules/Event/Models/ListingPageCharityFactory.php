<?php

namespace Database\Factories\Modules\Event\Models;

use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;
use App\Enums\ListingPageCharityTypeEnum;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\ListingPageCharity>
 */
class ListingPageCharityFactory extends CustomFactory
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
            'type' => $this->faker->randomElement(ListingPageCharityTypeEnum::cases()),
        ];
    }
}
