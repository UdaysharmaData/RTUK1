<?php

namespace Database\Factories;

use Auth;
use App\Models\ApiClient;
use App\Models\Experience;
use App\Models\EventExperience;
use App\Modules\Event\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventExperience>
 */
class EventExperienceFactory extends Factory
{
    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (EventExperience $eventExperience) {
            if (($values = $eventExperience->experience->values) && in_array($eventExperience->experience->name, array_keys($this->descriptions), true)) {
                $eventExperience->value = $values[rand(0, count($values) - 1)];
                $eventExperience->description = $this->descriptions[$eventExperience->experience->name];
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
            'event_id' => Event::factory(),
            'experience_id' => Experience::factory(),
            'value' => $this->faker->word(),
            'description' => $this->faker->sentence()
        ];
    }

    public $descriptions = [
        'Atmosphere' => 'Based on <b>20,000</b> participants',
        'Scenery' => 'Based on location and reviews',
        'Elevation' => '<b>20 to 50m</b> elevation gain per km',
        'Trail' => 'Trail running events',
    ];
}
