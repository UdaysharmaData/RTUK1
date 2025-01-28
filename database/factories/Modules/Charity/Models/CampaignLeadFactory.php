<?php

namespace Database\Factories\Modules\Charity\Models;

use Database\Factories\CustomFactory;
use App\Enums\CampaignLeadChannelEnum;
use App\Modules\Charity\Models\Campaign;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\CampaignLead>
 */
class CampaignLeadFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'campaign_id' => Campaign::factory(),
            'channel' => $this->faker->randomElement(CampaignLeadChannelEnum::cases()),
            'count' => $this->faker->randomDigit(),
            'threshold' => $this->faker->randomElement([$this->faker->randomDigit(), null]),
            'notification_trigger' => $this->faker->randomElement([$this->faker->randomNumber(2), null])
        ];
    }
}
