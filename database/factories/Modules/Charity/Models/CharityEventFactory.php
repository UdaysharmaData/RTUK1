<?php

namespace Database\Factories\Modules\Charity\Models;

use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\CharityEvent>
 */
class CharityEventFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event_id' => Event::factory(),
            'charity_id' => Charity::factory(),
            'type' => $this->faker->randomElement(['included', 'excluded'])
        ];
    }
}
