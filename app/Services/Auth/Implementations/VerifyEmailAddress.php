<?php

namespace App\Services\Auth\Implementations;

use App\Modules\User\Models\User;
use App\Services\Auth\Conditions\HasVerifiableAttribute;
use App\Services\Auth\Conditions\NotYetVerifiedAttribute;
use App\Services\Auth\Enums\NotificationType;
use App\Services\Auth\Notifications\SendVerificationCode;
use App\Services\Auth\VerifyAccountAttribute;
use Exception;
use JetBrains\PhpStorm\Pure;

class VerifyEmailAddress extends VerifyAccountAttribute
{
    /**
     * @param User $user
     * @param string $attribute
     */
    #[Pure] public function __construct(public User $user, public string $attribute = 'email')
    {
        parent::__construct($this->user, $this->attribute);
    }

    /**
     * @return $this
     */
    public function setValidationConditions(): static
    {
        $this->validationConditions[] = new HasVerifiableAttribute($this);
        $this->validationConditions[] = new NotYetVerifiedAttribute($this);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function sendVerificationCode(): void
    {
        $this->user->notify(new SendVerificationCode(NotificationType::Email, 'verify_email', $this->generateCode()));
    }
}
