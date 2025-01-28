<?php

namespace Database\Factories\Modules\Participant\Models;

use Database\Factories\CustomFactory;
use App\Modules\Event\Models\EventCustomField;
use App\Modules\Participant\Models\Participant;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Participant\Models\ParticipantCustomField>
 */
class ParticipantCustomFieldFactory extends CustomFactory
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
            'event_custom_field_id' => EventCustomField::factory(),
            'value' => $this->faker->word()
        ];
    }
}
