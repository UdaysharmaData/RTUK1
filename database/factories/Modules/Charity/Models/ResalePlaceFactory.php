<?php

namespace Database\Factories\Modules\Charity\Models;

use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\ResalePlace>
 */
class ResalePlaceFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $place = $this->faker->randomNumber(3);

        return [
            'charity_id' => Charity::factory(),
            'event_id' => Event::factory(),
            'places' => $place,
            'taken' => rand(1, $place),
            'unit_price' => $this->faker->randomNumber(3),
            'discount' => $this->faker->randomElement([null, null, rand(2, 20)])
        ];
    }
}
