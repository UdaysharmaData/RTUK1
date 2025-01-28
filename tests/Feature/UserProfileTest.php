<?php

namespace Tests\Feature;

use Database\Factories\UserTestFactory;
use Database\Seeders\TestPrepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that user is assigned a Profile.
     *
     * @return void
     */
    public function test_that_user_is_assigned_a_profile()
    {
        $this->seed(TestPrepSeeder::class);

        $user = UserTestFactory::new()->create();

        $this->assertNotEmpty($user->profile);
    }
}
