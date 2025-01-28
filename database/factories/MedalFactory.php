<?php

namespace Database\Factories;

use App\Enums\MedalTypeEnum;
use Database\Traits\SiteTrait;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Database\Factories\Factory<\App\Models\Medal>
 */
class MedalFactory extends Factory
{
    use SiteTrait;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $medal = $this->faker->randomElement([
            \App\Modules\Event\Models\Event::class,
            \App\Modules\Event\Models\EventCategory::class
        ]);

        return [
            'medalable_id' => $medal::factory(),
            'medalable_type' => $medal,
            'site_id' => static::getSite()?->id,
            'name' => $this->faker->unique()->name(),
            'type' => $this->faker->randomElement(MedalTypeEnum::cases())->value,
            'description' => $this->faker->text()
        ];
    }
}
