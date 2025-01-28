<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\User\Models\Profile;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\User\Models\ParticipantProfile>
 */
class ParticipantProfileFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'profile_id' => Profile::factory(),
            'fundraising_url' => $this->faker->url(),
            'slogan' => $this->faker->randomElement(['Everything is impossible until we get it done!', 'Strong like the Rock of Gibraltar', 'Love to keep your body & brain healthy!', null]),
            'club' => $this->faker->randomElement(['London City Runner', 'East End Road Runners', 'Trent Park Running Club', 'Chorlton Runners', 'Run Sandymoor', null])
        ];
    }
}
