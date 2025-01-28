<?php

namespace Database\Factories;

use Str;
use App\Models\Venue;
use Database\Traits\SiteTrait;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Database\Factories\Factory<\App\Models\Venue>
 */
class VenueFactory extends Factory
{
    use SiteTrait;

    /**
     * Create the venue's image
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (Venue $venue) { // Ensure the venue does not exists for the site making the request
            $_venue = Venue::where('site_id', $venue->site_id)
                ->where('name', $venue->name);

            if ($_venue->exists()) {
                $venue->name .= Str::random(10);
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
        $venues = ['Bedford Autodrome', 'Goodwood Motor Circuit', 'Hampton Court Palace', 'Richmond Park', 'Oulton Park Circuit', 'Kempton Park Racecourse', 'Newbury Racecourse', 'Victoria Park', 'Egham Cricket Ground', 'Aintree Racecourse', 'Heaton Park', 'Brockwell Park', 'Olympic Park', 'Lee Valley Velo Park', 'Crystal Palace Park', 'Richmond Park', 'Finsbury Park', 'Tatton Park', 'Olympic Park'];

        return [
            'site_id' => static::getSite()?->id,
            'name' => $this->faker->randomElement($venues),
            'description' => $this->faker->text(),
        ];
    }
}
