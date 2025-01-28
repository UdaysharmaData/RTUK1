<?php

namespace Database\Factories\Modules\User\Models;

use App\Enums\RoleNameEnum;
use Illuminate\Support\Str;
use Database\Traits\SiteTrait;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Modules\User\Models\Profile;
use Database\Factories\CustomFactory;
use App\Modules\User\Models\ParticipantProfile;

/**
 * @extends \Database\Factories\CustomFactory<\App\Modules\User\Models\User>
 */
class UserFactory extends CustomFactory
{
    use SiteTrait;

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (User $user) {

        })->afterCreating(function (User $user) {
            $user->createPasswordRecord($user->password);

            $user->assignRole(RoleNameEnum::Participant);
    
            $user->assignDefaultActiveRole();

            // Create the user profile
            $profile = Profile::factory()
                ->for($user)
                ->create();

            // Create the participant profile
            ParticipantProfile::factory()
                ->for($profile)
                ->create();
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $site = static::getSite()->load('apiClients');
        $site->load('apiClients');

        return [
            'api_client_id' => $site->apiClients[0]?->id,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => Str::random(5).$this->faker->unique()->safeEmail(), // Appending a random string increases the changes of getting a unique email.
            'phone' => null,
            'temp_pass' => null,
            'email_verified_at' => null,
            'phone_verified_at' => null,
            'password' => $password = '$2y$10$TuEsN6GkDAzUpo.u3DTsLufO8/y8693a0NKzU5ku7QnGLSGgrHGLa', // Password.0!
            // 'password' => Hash::make('Password.0!'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
