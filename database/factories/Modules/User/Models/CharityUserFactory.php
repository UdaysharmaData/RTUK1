<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\User\Models\User;
use App\Enums\CharityUserTypeEnum;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\User\Models\CharityUser>
 */
class CharityUserFactory extends CustomFactory
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
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(CharityUserTypeEnum::cases())
        ];
    }
}
