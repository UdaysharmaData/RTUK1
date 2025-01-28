<?php

namespace Tests\Feature;

use Database\Seeders\TestPrepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that user can be created with valid details.
     *
     * @return void
     */
    public function test_that_user_can_register_with_valid_credentials()
    {
        $this->seed(TestPrepSeeder::class);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/auth/register', [
                'first_name' => 'Sally',
                'last_name' => 'King',
                'email' => 'sallyking@email.com',
                'phone' => '08000000000',
                'password' => \Illuminate\Support\Facades\Hash::make(env('VALID_PASSWORD_SAMPLE'))
            ]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['status', 'message', 'data', 'data.token'])
            );
    }

    /**
     * Test that user cannot register without a valid email.
     *
     * @return void
     */
    public function test_that_user_cannot_register_without_valid_email()
    {
        $this->seed(TestPrepSeeder::class);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/auth/register', [
                'first_name' => 'Sally',
                'last_name' => 'King',
                'email' => 'sallyking',
                'phone' => '08000000000',
                'password' => \Illuminate\Support\Facades\Hash::make(env('VALID_PASSWORD_SAMPLE'))
            ]);

        $response->assertStatus(422)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['message', 'errors.email'])
            );
    }

    /**
     * Test that user cannot register without a first_name.
     *
     * @return void
     */
    public function test_that_user_cannot_register_without_first_name()
    {
        $this->seed(TestPrepSeeder::class);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/auth/register', [
                'first_name' => '',
                'last_name' => 'King',
                'email' => 'sallyking@email.com',
                'phone' => '08000000000',
                'password' => \Illuminate\Support\Facades\Hash::make(env('VALID_PASSWORD_SAMPLE'))
            ]);

        $response->assertStatus(422)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['message', 'errors.first_name'])
            );
    }

    /**
     * Test that user cannot register without a last_name.
     *
     * @return void
     */
    public function test_that_user_cannot_register_without_last_name()
    {
        $this->seed(TestPrepSeeder::class);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/auth/register', [
                'first_name' => 'Steve',
                'last_name' => '',
                'email' => 'sallyking@email.com',
                'phone' => '08000000000',
                'password' => \Illuminate\Support\Facades\Hash::make(env('VALID_PASSWORD_SAMPLE'))
            ]);

        $response->assertStatus(422)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['message', 'errors.last_name'])
            );
    }

    /**
     * Test that user cannot register without a phone number.
     *
     * @return void
     */
    public function test_that_user_cannot_register_without_phone_number()
    {
        $this->seed(TestPrepSeeder::class);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/auth/register', [
                'first_name' => 'Marcus',
                'last_name' => 'King',
                'email' => 'marcus.king@email.com',
                'phone' => '',
                'password' => \Illuminate\Support\Facades\Hash::make(env('VALID_PASSWORD_SAMPLE'))
            ]);

        $response->assertStatus(422)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['message', 'errors.phone'])
            );
    }

    /**
     * Test that user cannot register without a valid password.
     *
     * @return void
     */
    public function test_that_user_cannot_register_without_valid_password()
    {
        $this->seed(TestPrepSeeder::class);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/auth/register', [
                'first_name' => 'Marcus',
                'last_name' => 'King',
                'email' => 'marcus.king@email.com',
                'phone' => '08000000000',
                'password' => 'password'
            ]);

        $response->assertStatus(422)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['message', 'errors.password'])
            );
    }
}
