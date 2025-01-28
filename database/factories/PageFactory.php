<?php

namespace Database\Factories;

use Database\Traits\SiteTrait;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    use SiteTrait;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'site_id' => static::getSite()?->id,
            'name' => $this->faker->name(),
            'url' => $this->faker->url(),
            'status' => $this->faker->boolean(90)
        ];
    }
}
