<?php

namespace Database\Factories\Modules\Charity\Models;

use App\Modules\User\Models\User;
use App\Enums\CampaignStatusEnum;
use App\Enums\CampaignPackageEnum;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\Campaign>
 */
class CampaignFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $titles = [
            'Virtual Marathon Series Blesma',
            'Blue Cross - Premium Campaign',
            'JAM - Premium Campaign',
            'Livingstone Tanzania Trust - Classic Campaign',
            'The Girls\' Network - Classic Campaign'
        ];

        return [
            'charity_id' => Charity::factory(),
            // 'user_id' => User::factory(),
            'title' => $this->faker->randomElement($titles),
            // 'package' => $this->faker->randomElement(CampaignPackageEnum::cases()),
            'package' => '25_leads',
            'status' => $this->faker->randomElement(CampaignStatusEnum::cases()),
            'start_date' => $this->faker->dateTime(),
            'end_date' => $this->faker->dateTime(),
            'notification_trigger' => $this->faker->randomElement([$this->faker->randomDigit(), null])
        ];
    }
}
