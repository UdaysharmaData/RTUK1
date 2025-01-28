<?php

namespace Tests\Feature;

use Database\Factories\UserTestFactory;
use Database\Seeders\TestPrepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that user is assigned profile image.
     *
     * @return void
     */
    public function test_that_user_has_a_profile_image()
    {
        $this->seed(TestPrepSeeder::class);

        $user = UserTestFactory::new()->create();

        $this->assertNotEmpty($user->profile->avatar_url);
    }
}
