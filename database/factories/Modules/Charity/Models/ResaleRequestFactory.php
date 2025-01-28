<?php

namespace Database\Factories\Modules\Charity\Models;

use Str;
use App\Enums\ResaleRequestStateEnum;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\ResalePlace;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\ResaleRequest>
 */
class ResaleRequestFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'resale_place_id' => ResalePlace::factory(),
            'charity_id' => Charity::factory(),
            'state' => $this->faker->randomElement(ResaleRequestStateEnum::cases()),
            'places' => $this->faker->randomNumber(3),
            'unit_price' => $this->faker->randomNumber(3),
            'discount' => $this->faker->randomElement([null, null, rand(2, 20)]),
            'contact_email' => $this->faker->safeEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'note' => $this->faker->text(),
            'charge_id' => $this->faker->randomElement([null, null, Str::random(6)])
        ];
    }
}
