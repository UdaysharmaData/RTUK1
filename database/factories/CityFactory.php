<?php

namespace Database\Factories;

use Str;
use App\Models\City;
use Database\Traits\SiteTrait;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Database\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    use SiteTrait;

    /**
     * Create the city's image
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (City $city) { // Ensure the city does not exists for the site making the request
            $_city = City::where('site_id', $city->site_id)
                ->where('name', $city->name);

            if ($_city->exists()) {
                $city->name .= Str::random(10);
            }
        });
    }

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
            'description' => $this->faker->text(),
        ];
    }
}
