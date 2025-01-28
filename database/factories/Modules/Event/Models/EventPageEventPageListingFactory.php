<?php

namespace Database\Factories\Modules\Event\Models;

use Database\Factories\CustomFactory;
use App\Modules\Event\Models\EventPage;
use App\Modules\Event\Models\EventPageListing;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventPageEventPageListing>
 */
class EventPageEventPageListingFactory extends CustomFactory
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
            'event_page_id' => EventPage::factory(),
            'video' => null
        ];
    }
}
