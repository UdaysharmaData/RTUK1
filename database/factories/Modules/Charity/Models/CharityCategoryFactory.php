<?php

namespace Database\Factories\Modules\Charity\Models;

use Str;
use Database\Factories\CustomFactory;
// use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\CharityCategory>
 */
class CharityCategoryFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = $this->faker->unique()->name();
        $slug = Str::slug($name);

        return [
            'status' => $this->faker->boolean(90),
            'name' => $name,
            'slug' => $slug,
        ];
    }
}
