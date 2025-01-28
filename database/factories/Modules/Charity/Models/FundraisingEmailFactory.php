<?php

namespace Database\Factories\Modules\Charity\Models;

use App\Enums\FundraisingEmailScheduleTypeEnum;
use App\Enums\FundraisingEmailTemplateEnum;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\FundraisingEmail>
 */
class FundraisingEmailFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $subjects = ['Fundraising Support', 'Thank You Email', 'Welcome Email'];

        $names = ['Fundraising Details', 'Thank You Email', 'Welcome Email'];

        return [
            'status' => $this->faker->boolean(),
            'name' => $this->faker->randomElement($names),
            'subject' => $this->faker->randomElement($subjects),
            'schedule_type' => $this->faker->randomElement(FundraisingEmailScheduleTypeEnum::cases()),
            'schedule_days' => $this->faker->randomElement([1, 30, 7]),
            'template' => $this->faker->randomElement(FundraisingEmailTemplateEnum::cases())
        ];
    }
}
