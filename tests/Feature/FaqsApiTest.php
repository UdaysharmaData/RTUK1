<?php

namespace Tests\Feature;

use Database\Seeders\TestPrepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class FaqsApiTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that FAQs can be retrieved via API endpoint.
     *
     * @return void
     */
    public function test_that_faqs_endpoint_returns_valid_response()
    {
        $this->seed(TestPrepSeeder::class);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/client/faqs');

        $response->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['status', 'message', 'data', 'data.faqs'])
            );
    }
}
