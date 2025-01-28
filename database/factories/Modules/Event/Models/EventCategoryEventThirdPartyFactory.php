<?php

namespace Database\Factories\Modules\Event\Models;

use Database\Factories\CustomFactory;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventThirdParty;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventCategoryEventThirdParty>
 */
class EventCategoryEventThirdPartyFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event_third_party_id' => EventThirdParty::factory(),
            'event_category_id' => EventCategory::factory(),
            'external_id' => $this->faker->randomElement([996120, 994862, 1009974])
        ];
    }
}
