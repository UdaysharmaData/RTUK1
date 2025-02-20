<?php

namespace App\Services\TwoFactorAuth\Contracts;

use DateTimeInterface;
use Illuminate\Contracts\Support\Renderable;
use Stringable;

interface TwoFactorOtpCode extends Renderable, Stringable
{

    /**
     * Creates a Code for a given timestamp, optionally by a given period offset.
     * @param DateTimeInterface|int|string $at
     * @param int $offset
     * @return string
     */
    public function makeCode(DateTimeInterface|int|string $at = 'now', int $offset = 0): string;

    /**
     * Validates a given code, optionally for a given timestamp and future window
     * @param string $code
     * @param DateTimeInterface|int|string $at
     * @param int|null $window
     * @return bool
     */
    public function validateCode(string $code, DateTimeInterface|int|string $at = 'now', int $window = null): bool;

    /**
     * Returns the Shared Secret as a QR Code.
     *
     * @return string
     */
    public function toQr(): string;

    /**
     * Returns the Shared Secret as a string.
     *
     * @return string
     */
    public function toString(): string;

}
