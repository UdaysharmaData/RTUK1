<?php

namespace Database\Factories\Modules\Charity\Models;

use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Campaign;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\CampaignEvent>
 */
class CampaignEventFactory extends CustomFactory
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
            'event_id' => Event::factory()
        ];
    }
}