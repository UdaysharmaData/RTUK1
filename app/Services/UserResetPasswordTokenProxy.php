<?php

namespace App\Services;

class UserResetPasswordTokenProxy
{
    public string $resetToken;

    public function __construct(string $resetToken)
    {
        $this->resetToken = $resetToken;
    }

    /**
     * @param $token
     * @return void
     */
    public function setResetToken($token)
    {
        $this->resetToken = $token;
    }

    /**
     * @return string
     */
    public function getResetToken(): string
    {
        return $this->resetToken;
    }
}
