<?php

namespace App\Services\TwoFactorAuth\Contracts;

use DateTimeInterface;

interface TwoFactorAuth
{
    /**
     * Recreates the Two-Factor Authentication from the ground up, and returns a new Shared Secret.
     * @return TwoFactorOtpCode
     */
    public function initializeTwoFactorAuth(): TwoFactorOtpCode;

    /**
     * Enables a specific 2-factor auth method for the given user
     * @return void
     */
    public function enableTwoFactorAuth(): void;

    /**
     * Disables a specific 2-factor auth method for  the give user
     * @return void
     */
    public function disableTwoFactorAuth(): void;


    /**
     * Confirms the shared secret and fully enables the Two-Facor Authentication
     * @param string $code
     * @return void
     */
    public function confirmTwoFactorAuth(string $code): void;

    /**
     * Validates the TOTP Code or Recovery Code.
     *
     * @param  string|null  $code
     * @param  bool  $useRecoveryCodes
     * @return bool
     */
    public function validateTwoFactorCode(?string $code = null, bool $useRecoveryCodes = true): bool;

    /**
     * Makes a Two-Factor Code for a given time, and period offset.
     *
     * @param DateTimeInterface|int|string  $at
     * @param  int  $offset
     * @return string
     */
    public function makeTwoFactorCode(DateTimeInterface|int|string $at = 'now', int $offset = 0): string;


}
