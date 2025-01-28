<?php

namespace Database\Factories\Modules\Participant\Models;

use App\Enums\GenderEnum;
use App\Enums\ProfileEthnicityEnum;
use Database\Factories\CustomFactory;
use App\Modules\Participant\Models\Participant;
use App\Enums\ParticipantProfileWeeklyPhysicalActivityEnum;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Participant\Models\ParticipantExtra>
 */
class ParticipantExtraFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'participant_id' => Participant::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'dob' => null,
            'phone' => null,
            'gender' => $this->faker->randomElement(GenderEnum::cases()),
            'ethnicity' => $this->faker->randomElement(ProfileEthnicityEnum::cases()),
            'weekly_physical_activity' => $this->faker->randomElement(ParticipantProfileWeeklyPhysicalActivityEnum::cases()),
        ];
    }
}
