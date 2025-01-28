<?php

namespace Database\Factories\Modules\Participant\Models;

use App\Modules\User\Models\User;
use App\Enums\ParticipantStatusEnum;
use Database\Factories\CustomFactory;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventEventCategory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Participant\Models\Participant>
 */
class ParticipantFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'event_event_category_id' => EventEventCategory::factory(),
            'charity_id' => Charity::factory(),
            'corporate_id' => null,
            'status' => $this->faker->randomElement(ParticipantStatusEnum::cases()),
            'waive' => $this->faker->randomElement(ParticipantWaiveEnum::cases()),
            'waiver' => $this->faker->randomElement(ParticipantWaiverEnum::cases()),
            'added_via' => $this->faker->randomElement(ParticipantAddedViaEnum::cases()),
        ];
    }
}
