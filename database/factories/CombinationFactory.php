<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Region;
use App\Models\Venue;
use Database\Traits\SiteTrait;
use App\Modules\Event\Models\EventCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Database\Factories\Factory<\App\Models\Combination>
 */
class CombinationFactory extends Factory
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
            'city_id' => City::factory(),
            'region_id' => Region::factory(),
            'venue_id' => Venue::factory(),
            'event_category_id' => EventCategory::factory(),
            'name' => $this->faker->unique()->name(),
            'description' => $this->faker->text()
        ];
    }
}
