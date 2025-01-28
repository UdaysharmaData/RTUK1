<?php

namespace Database\Factories\Modules\Participant\Models;

use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use Database\Factories\CustomFactory;
use App\Enums\ParticipantActionTypeEnum;
use App\Modules\Participant\Models\Participant;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Participant\Models\ParticipantAction>
 */
class ParticipantActionFactory extends CustomFactory
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
            'user_id' => User::factory(),
            'role_id' => Role::factory(),
            'type' => $this->faker->randomElement(ParticipantActionTypeEnum::cases()),
        ];
    }
}
