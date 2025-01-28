<?php

namespace App\Services\Auth\Conditions;

use App\Services\Auth\Contracts\VerifiableConditionInterface;
use App\Services\Auth\Exceptions\InvalidVerifiableAttribute;
use App\Services\Auth\VerifyAccountAttribute;

class HasVerifiableAttribute implements VerifiableConditionInterface
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
        return $this->verifyAccountAttribute->user["{$this->verifyAccountAttribute->attribute}"];
    }

    /**
     * @throws InvalidVerifiableAttribute
     */
    public function handleIfConditionFails()
    {
        throw new InvalidVerifiableAttribute("User has no [{$this->verifyAccountAttribute->attribute}] attribute");
    }
}
