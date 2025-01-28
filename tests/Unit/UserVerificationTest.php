<?php

namespace Tests\Unit;

use App\Modules\User\Models\User;
use App\Services\Auth\Notifications\SendVerificationCode;
use Database\Seeders\TestPrepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserVerificationTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that user receives verification code on successful registration.
     *
     * @return void
     */
    public function test_that_user_is_send_account_verification_code_after_account_is_created()
    {
        $this->seed(TestPrepSeeder::class);

        Notification::fake();

        $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/auth/register', [
                'first_name' => 'Sally',
                'last_name' => 'King',
                'email' => 'sallyking@email.com',
                'phone' => '08000000000',
                'password' => \Illuminate\Support\Facades\Hash::make(env('VALID_PASSWORD_SAMPLE'))
            ]);

        $user = User::firstWhere('email', 'sallyking@email.com');

        Notification::assertSentTo(
            [$user], SendVerificationCode::class
        );
    }
}
