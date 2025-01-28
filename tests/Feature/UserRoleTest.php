<?php

namespace Tests\Feature;

use Database\Factories\UserTestFactory;
use Database\Seeders\TestPrepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that user is assigned a role.
     *
     * @return void
     */
    public function test_that_user_is_assigned_a_role()
    {
        $this->seed(TestPrepSeeder::class);

        $user = UserTestFactory::new()->create();

        $this->assertNotEmpty($user->roles);
    }
}
