<?php

namespace Tests\Feature;

use Database\Factories\UserTestFactory;
use Database\Seeders\TestPrepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that API client User can retrieve their notifications.
     *
     * @return void
     */
    public function test_that_client_user_can_retrieve_notifications()
    {
        $this->seed(TestPrepSeeder::class);

        $response = $this->actingAs(UserTestFactory::new()->make(), 'api')
            ->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['status', 'message', 'data', 'data.notifications'])
            );
    }
}
