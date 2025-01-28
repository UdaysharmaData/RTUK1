<?php

namespace App\Services\Auth\Conditions;

use App\Services\Auth\Contracts\VerifiableConditionInterface;
use App\Services\Auth\Exceptions\AlreadyVerifiedAttribute;
use App\Services\Auth\VerifyAccountAttribute;

class NotYetVerifiedAttribute implements VerifiableConditionInterface
{
    /**
     * @param VerifyAccountAttribute $verifyAccountAttribute
     */
    public function __construct(private VerifyAccountAttribute $verifyAccountAttribute)
    {
    }

    /**
     * @return bool
     */
    public function isPassed(): bool
    {
        return is_null($this->verifyAccountAttribute->user["{$this->verifyAccountAttribute->attribute}_verified_at"]);
    }

    /**
     * @throws AlreadyVerifiedAttribute
     */
    public function handleIfConditionFails()
    {
        throw new AlreadyVerifiedAttribute("User's [{$this->verifyAccountAttribute->attribute}] is already verified.");
    }
}
