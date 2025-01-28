<?php

namespace App\Services\Auth\Implementations;

use App\Modules\User\Models\User;
use App\Services\Auth\Enums\NotificationType;
use App\Services\Auth\Notifications\SendVerificationCode;
use App\Services\Auth\VerifyAccountAttribute;
use Exception;
use JetBrains\PhpStorm\Pure;

class VerifyPhoneNumber extends VerifyAccountAttribute
{
    /**
     * @param User $user
     * @param string $attribute
     */
    #[Pure] public function __construct(public User $user, public string $attribute = 'phone')
    {
        parent::__construct($this->user, $this->attribute);
    }

    /**
     * @throws Exception
     */
    public function sendVerificationCode(): void
    {
        $this->user->notify(new SendVerificationCode(NotificationType::Phone, 'verify_phone', $this->generateCode()));
    }

    /**
     * @return $this
     */
    public function setValidationConditions(): static
    {
        // TODO: Implement setValidationConditions() method.
    }
}
