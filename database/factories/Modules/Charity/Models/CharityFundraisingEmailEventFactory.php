<?php

namespace Database\Factories\Modules\Charity\Models;

use App\Modules\Event\Models\Event;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\CharityFundraisingEmail;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\CharityFundraisingEmailEvent>
 */
class CharityFundraisingEmailEventFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'charity_fundraising_email_id' => CharityFundraisingEmail::factory(),
            'event_id' => Event::factory()
        ];
    }
}