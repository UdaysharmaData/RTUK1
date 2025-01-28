<?php

namespace App\Services\Auth\Contracts;

interface VerifiableConditionInterface
{
    public function isPassed();

    public function handleIfConditionFails();
}
