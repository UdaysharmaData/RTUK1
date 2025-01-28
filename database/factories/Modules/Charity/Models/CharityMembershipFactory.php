<?php

namespace Database\Factories\Modules\Charity\Models;

use Carbon\Carbon;
use Database\Factories\CustomFactory;
use App\Modules\Charity\Models\Charity;
use App\Enums\CharityMembershipTypeEnum;
use App\Modules\Charity\Models\CharityMembership;

// use Illuminate\Database\Eloquent\Factories\Factory;

/**
// * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Charity\Models\CharityMembership>
 * @extends \Database\Factories\CustomFactory<\App\Modules\Charity\Models\CharityMembership>
 */
class CharityMembershipFactory extends CustomFactory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'charity_id' => Charity::factory(),
            'type' => $this->faker->randomElement(CharityMembershipTypeEnum::cases()),
            'status' => $this->faker->randomElement([CharityMembership::ACTIVE, CharityMembership::INACTIVE]),
            'use_new_membership_fee' => $this->faker->boolean(90),
            'renewed_on' => Carbon::now()->toDateString(),
            // 'start_date' => Carbon::now()->toDateString(),
            'expiry_date' => Carbon::now()->addDays(3)->toDateString()
        ];
    }
}
