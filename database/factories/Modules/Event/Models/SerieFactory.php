<?php

namespace Database\Factories\Modules\Event\Models;

use Database\Traits\SiteTrait;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\Serie>
 */
class SerieFactory extends CustomFactory
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
            'name' => $this->faker->unique()->name(),
            'description' => $this->faker->text()
        ];
    }
}
