<?php

namespace Database\Factories\Modules\Event\Models;

use App\Models\Region;
use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\PromotionalFeaturedEvent>
 */
class PromotionalFeaturedEventFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'region_id' => Region::factory(),
            'event_id' => Event::factory()
        ];
    }
}