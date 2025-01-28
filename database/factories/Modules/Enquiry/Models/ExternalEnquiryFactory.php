<?php

namespace Database\Factories\Modules\Enquiry\Models;

use App\Enums\GenderEnum;
use Database\Traits\SiteTrait;
use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;
use App\Modules\Partner\Models\PartnerChannel;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Enquiry\Models\ExternalEnquiry>
 */
class ExternalEnquiryFactory extends CustomFactory
{
    use SiteTrait;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $timeline = [
            [
                "caption" => "Enquiry Received",
                "value" => true,
                "datetime" => [
                    "date" => "2020-06-07 11:43:37.000000",
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ]
            ]
        ];

        return [
            'site_id' => static::getSite()?->id,
            'charity_id' => Charity::factory(),
            'event_id' => Event::factory(),
            'partner_channel_id' => PartnerChannel::factory(),
            // 'participant_id' => null,
            'contacted' => $this->faker->boolean(70),
            'converted' => $this->faker->boolean(70),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'gender' => $this->faker->randomElement(GenderEnum::cases()),
            'postcode' => $this->faker->postcode(),
            'comments' => $this->faker->sentence(),
            'timeline' => $timeline,
            'token' => $this->faker->macPlatformToken()
        ];
    }
}
