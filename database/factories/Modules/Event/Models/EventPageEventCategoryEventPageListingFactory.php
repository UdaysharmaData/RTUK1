<?php

namespace Database\Factories\Modules\Event\Models;

use Database\Factories\CustomFactory;
use App\Modules\Event\Models\EventPage;
use App\Modules\Event\Models\EventCategoryEventPageListing;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventPageEventCategoryEventPageListing>
 */
class EventPageEventCategoryEventPageListingFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event_category_event_page_listing_id' => EventCategoryEventPageListing::factory(),
            'event_page_id' => EventPage::factory()
        ];
    }
}
