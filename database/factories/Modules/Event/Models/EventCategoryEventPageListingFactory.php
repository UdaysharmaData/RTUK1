<?php

namespace Database\Factories\Modules\Event\Models;

use Database\Factories\CustomFactory;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventPageListing;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventCategoryEventPageListing>
 */
class EventCategoryEventPageListingFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event_page_listing_id' => EventPageListing::factory(),
            'event_category_id' => EventCategory::factory(),
            'priority' => $this->faker->randomNumber(2)
        ];
    }
}
