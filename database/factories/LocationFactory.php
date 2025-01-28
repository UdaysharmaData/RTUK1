<?php

namespace Database\Factories;

use App\Enums\LocationUseAsEnum;
use Database\Factories\CustomFactory;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @extends \Database\Factories\CustomFactory<\App\Models\Location>
 */
class LocationFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $addresses = ['Manchester Cheshire	England', 'Heart Park, Warwickshire', 'Southampton Common, Southampton SO15 7NN', 'Guildhall Square, Market Walk, Salisbury SP1 1JH', 'Farley Ln, Alton, Stoke-on-Trent ST10 4DB, UK', 'Donegall Quay, BT1 3EA', 'M25 2SW', 'E9 5HT', 'E20 3AB', 'Packington Estate, Meriden, Coventry CV7 7HE'];

        $location = $this->faker->randomElement([
            \App\Modules\Event\Models\Event::class,
            \App\Modules\Charity\Models\Charity::class
        ]);

        return [
            'locationable_id' => $location::factory(),
            'locationable_type' => $location,
            // 'locationable_type' => array_search($location, Relation::$morphMap),
            'address' => $this->faker->randomElement($addresses),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'use_as' => $this->faker->randomElement(LocationUseAsEnum::cases())
        ];
    }
}
