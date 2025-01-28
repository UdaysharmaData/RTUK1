<?php

namespace Database\Factories\Modules\Event\Models;

use App\Modules\User\Models\User;
use Database\Factories\CustomFactory;
use App\Enums\EventManagerCompleteNotificationsEnum;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventManager>
 */
class EventManagerFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'complete_notifications' => $this->faker->randomElement(EventManagerCompleteNotificationsEnum::cases())
        ];
    }
}
