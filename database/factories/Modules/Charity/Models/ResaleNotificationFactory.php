<?php

namespace Database\Factories\Modules\Charity\Models;

use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\ResaleNotification>
 */
class ResaleNotificationFactory extends CustomFactory
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
            'event_id' => Event::factory(),
            'status' => $this->faker->boolean(95)
        ];
    }
}