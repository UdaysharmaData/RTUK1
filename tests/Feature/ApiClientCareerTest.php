<?php

namespace Tests\Feature;

use App\Enums\RoleNameEnum;
use App\Models\ApiClientCareer;
use Database\Factories\UserTestFactory;
use Database\Seeders\TestPrepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ApiClientCareerTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    /**
     * Test that API client User can retrieve career listings.
     *
     * @return void
     */
    public function test_that_client_user_can_retrieve_career_listings()
    {
        $this->seed(TestPrepSeeder::class);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/client/careers');

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['status', 'message', 'data', 'data.careers'])
            );
    }

    /**
     * Test that API client User can view a career listing.
     *
     * @return void
     */
    public function test_that_client_user_can_view_a_career_listing()
    {
        $this->seed(TestPrepSeeder::class);
        $career = ApiClientCareer::factory()->create();

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->getJson("/api/v1/client/careers/$career->ref");

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['status', 'message', 'data', 'data.career'])
            );
    }

    /**
     * Test that API client User can add a career listing.
     *
     * @return void
     */
    public function test_that_client_admin_can_add_a_career_listing()
    {
        $this->seed(TestPrepSeeder::class);

        $response = $this->actingAs($this->getAdmin())
            ->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/portal/careers/store', [
                'title' => $this->faker->sentence,
                'description' => $this->faker->sentence(12),
                'link' => $this->faker->url
            ]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['status', 'message', 'data', 'data.career'])
            );
    }

    /**
     * Test that API client User can update a career listing.
     *
     * @return void
     */
    public function test_that_client_admin_can_update_a_career_listing()
    {
        $this->seed(TestPrepSeeder::class);
        $career = ApiClientCareer::factory()->create();

        $response = $this->actingAs($this->getAdmin())
            ->withHeaders(['Accept' => 'application/json'])
            ->patchJson("/api/v1/portal/careers/$career->ref/update", [
                'title' => $this->faker->sentence,
                'description' => $this->faker->sentence(12),
                'link' => $this->faker->url
            ]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['status', 'message', 'data', 'data.career'])
            );
    }

    /**
     * Test that API client User can delete a career listing.
     *
     * @return void
     */
    public function test_that_client_admin_can_delete_a_career_listing()
    {
        $this->seed(TestPrepSeeder::class);
        $career = ApiClientCareer::factory()->create();

        $response = $this->actingAs($this->getAdmin())
            ->withHeaders(['Accept' => 'application/json'])
            ->deleteJson("/api/v1/portal/careers/$career->ref/delete");

        $response->assertOk()
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->hasAll(['status', 'message', 'data'])
            );
    }

    /**
     * @return \App\Modules\User\Models\User|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|mixed
     */
    private function getAdmin(): mixed
    {
        return UserTestFactory::new()->create()
            ->unassignRole(RoleNameEnum::Participant)
            ->assignRole(RoleNameEnum::Administrator)
            ->refresh();
    }
}
