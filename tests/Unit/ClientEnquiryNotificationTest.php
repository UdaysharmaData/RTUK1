<?php

namespace Tests\Unit;

use App\Enums\EnquiryTypeEnum;
use App\Mail\NewContactUsEnquiry;
use App\Notifications\NewEnquiry;
use Database\Factories\UserTestFactory;
use Database\Seeders\TestPrepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\Helpers\General;
use Tests\TestCase;

class ClientEnquiryNotificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test that client admin is notified of a new enquiry.
     *
     * @return void
     */
    public function test_that_client_admin_is_notified_of_new_enquiry()
    {
        $this->seed(TestPrepSeeder::class);

        Mail::fake();

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/client/enquiries/store', [
                'full_name' => $this->faker->name,
                'email' => $this->faker->email,
                'message' => $this->faker->sentence(100),
                'enquiry_type' => General::randomizedEnquiryType(),
            ]);

        Mail::assertSent(NewContactUsEnquiry::class);
    }
}
