<?php

namespace Database\Factories\Modules\Event\Models;

use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Modules\Event\Models\EventCategory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventCategoryPromotionalEventFactory>
 */
class EventCategoryPromotionalEventFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event_category_id' => EventCategory::factory(),
            'event_id' => Event::factory(),
        ];
    }
}
