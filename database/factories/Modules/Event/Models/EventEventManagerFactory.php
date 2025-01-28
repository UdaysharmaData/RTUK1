<?php

namespace Database\Factories\Modules\Event\Models;

use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Modules\Event\Models\EventManager;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventEventManager>
 */
class EventEventManagerFactory extends CustomFactory
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
            'event_manager_id' => EventManager::factory()
        ];
    }
}
