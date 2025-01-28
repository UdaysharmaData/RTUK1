<?php

namespace Database\Factories\Modules\User\Models;

use Str;
use File;
use Carbon\Carbon;
use App\Enums\GenderEnum;
use Database\Traits\SiteTrait;
use App\Modules\User\Models\User;
use App\Enums\ProfileEthnicityEnum;
use App\Modules\User\Models\Profile;
use Database\Factories\CustomFactory;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\User\Models\Profile>
 */
class ProfileFactory extends CustomFactory
{
    use SiteTrait;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'gender' => $this->faker->randomElement(GenderEnum::cases()),
            'username' => $this->faker->randomElement([$this->faker->unique()->userName(), null]),
            // 'dob' => Carbon::today()->subYearsWithoutOverflow(60),
            'address' => $this->faker->address,
            'country' => $this->faker->country(),
            'nationality' => null,
            'state' => null,
            'city' => $this->faker->city(),
            'occupation' => $this->faker->jobTitle,
            'ethnicity' => $this->faker->randomElement(ProfileEthnicityEnum::cases()),
        ];
    }
}
