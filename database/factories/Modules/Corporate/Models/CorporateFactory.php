<?php

namespace Database\Factories\Modules\Corporate\Models;

use Str;
use App\Modules\User\Models\User;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Corporate\Models\Corporate>
 */
class CorporateFactory extends CustomFactory
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
            'slug' => Str::slug($this->faker->unique()->name())
        ];
    }
}
