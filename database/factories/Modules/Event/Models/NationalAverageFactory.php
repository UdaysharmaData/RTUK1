<?php

namespace Database\Factories\Modules\Event\Models;

use App\Enums\GenderEnum;
use Database\Factories\CustomFactory;
use App\Modules\Event\Models\EventCategory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\NationalAverage>
 */
class NationalAverageFactory extends CustomFactory
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
            'gender' => $this->faker->randomElement(GenderEnum::cases()),
            'year' => $this->faker->year(),
            'time' => $this->faker->time()
        ];
    }
}
