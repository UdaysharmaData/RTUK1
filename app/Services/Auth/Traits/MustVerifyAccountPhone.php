<?php

namespace App\Services\Auth\Traits;

use App\Services\Auth\Enums\NotificationType;
use App\Services\Auth\Notifications\SendVerificationCode;
use Exception;
use Illuminate\Support\Facades\Log;

trait MustVerifyAccountPhone
{
    private string $attributeName;

    /**
     * Determine if the user has verified their phone number.
     *
     * @return bool
     */
    public function hasVerifiedPhone(): bool
    {
        return ! is_null($this->phone_verified_at);
    }

    /**
     * Mark the given user's phone as verified.
     *
     * @return bool
     */
    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Get the phone number that should be used for verification.
     *
     * @return string
     */
    public function getPhoneForVerification(): string
    {
        return $this->attributeName ?: $this->phone;
    }

    /**
     * If phone number attribute is not [phone], set a string value.
     *
     * @param string $value
     * @return string
     */
    public function setPhoneAttributeName(string $value): string
    {
        return $this->attributeName = $value;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     * @throws Exception
     */
    public function sendPhoneVerificationNotification(): void
    {
        Log::info('send phone sms');
        $this->notify(new SendVerificationCode(NotificationType::Phone, 'verify_phone'));
    }
}
