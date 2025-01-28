<?php

namespace Database\Factories\Modules\Event\Models;

use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Enums\PredefinedPartnerChannelEnum;
use App\Modules\Partner\Models\PartnerChannel;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Event\Models\EventThirdPartyFactory>
 */
class EventThirdPartyFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event_id' => Event::factory(),
            'external_id' => $this->faker->randomElement([64765, 188053, 181811]),
            'partner_channel_id' => PartnerChannel::where('code', PredefinedPartnerChannelEnum::Bespoke->value)->value('id')
        ];
    }
}
