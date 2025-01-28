<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Enums\RoleNameEnum;
use App\Modules\User\Models\ParticipantProfile;
use Database\Traits\SiteTrait;
use Illuminate\Database\Seeder;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Profile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeploymentSeeder extends Seeder
{
    use /*WithoutModelEvents,*/ SiteTrait;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            DeploymentUserSeeder::class,
            DeploymentCharitySeeder::class,
            DeploymentEventSeeder::class,
            DeploymentEnquirySeeder::class,
            DeploymentParticipantSeeder::class,
            DeploymentOtherSeeder::class,
        ]);

        User::factory()->count(100000)->create([
            'password' => $password = '$2y$10$TuEsN6GkDAzUpo.u3DTsLufO8/y8693a0NKzU5ku7QnGLSGgrHGLa', // Password.0!
            'email_verified_at' => Carbon::now()->subDay()
        ])->each(function($user) use ($password) {
            $user->createPasswordRecord($password);

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
}
