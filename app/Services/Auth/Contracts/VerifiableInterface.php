<?php

namespace App\Services\Auth\Contracts;

Interface VerifiableInterface
{
    public function sendVerificationCode(): void;

    public function setValidationConditions(): static;
}
