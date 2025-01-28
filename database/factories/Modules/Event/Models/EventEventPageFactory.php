<?php

namespace Database\Factories\Modules\Event\Models;

use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Modules\Event\Models\EventPage;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventEventPage>
 */
class EventEventPageFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event_page_id' => EventPage::factory(),
            'event_id' => Event::factory(),
        ];
    }
}
