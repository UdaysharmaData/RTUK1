<?php

namespace Database\Factories\Modules\Event\Models;

use App\Enums\ListingPageTypeEnum;
use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\CharityListing;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\ListingPage>
 */
class ListingPageFactory extends CustomFactory
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
            'event_id' => Event::factory(),
            'title' => $this->faker->word(),
            'type' => $this->faker->randomElement(ListingPageTypeEnum::cases()),
            'event_page_description' => $this->faker->text()
        ];
    }
}
