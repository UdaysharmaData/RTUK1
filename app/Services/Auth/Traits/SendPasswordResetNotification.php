<?php

namespace App\Services\Auth\Traits;

use App\Services\Auth\Enums\NotificationType;
use App\Services\Auth\Notifications\SendVerificationCode;
use Exception;

trait SendPasswordResetNotification
{
    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @return void
     * @throws Exception
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new SendVerificationCode(NotificationType::Email,'password_reset', $token));
    }
}
