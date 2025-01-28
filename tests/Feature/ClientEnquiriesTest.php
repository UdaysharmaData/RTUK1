<?php

namespace Tests\Feature;

use App\Enums\EnquiryTypeEnum;
use Database\Seeders\TestPrepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Helpers\General;
use Tests\TestCase;

class ClientEnquiriesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test that client user/guest can submit a valid enquiry.
     *
     * @return void
     */
    public function test_that_client_user_can_make_valid_enquiry()
    {
        $this->seed(TestPrepSeeder::class);

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/client/enquiries/store', [
                'full_name' => $this->faker->name,
                'email' => $this->faker->email,
                'message' => $this->faker->sentence(100),
                'enquiry_type' => General::randomizedEnquiryType(),
            ]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->hasAll(['status', 'message', 'data'])
            );
    }

//    /**
//     * Test that client is able to request enquiry categories.
//     *
//     * @return void
//     */
//    public function test_that_client_can_retrieve_enquiry_categories()
//    {
//        $this->seed(TestPrepSeeder::class);
//
//        $response = $this->withHeaders(['Accept' => 'application/json'])
//            ->getJson('/api/v1/client/enquiries/contact-us');
//
//        $response->assertStatus(200)
//            ->assertJson(
//                fn (AssertableJson $json) => $json
//                    ->hasAll(['status', 'message', 'data', 'data.enquiry_types'])
//            );
//    }
}
