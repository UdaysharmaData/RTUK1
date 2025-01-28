<?php

namespace App\Services\Auth\Factories;

use App\Modules\User\Models\User;
use App\Services\Auth\Exceptions\UnsupportedVerificationService;
use App\Services\Auth\Implementations\VerifyEmailAddress;
use App\Services\Auth\Implementations\VerifyPhoneNumber;
use App\Services\Auth\VerifyAccountAttribute;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\Pure;

class AccountVerificationFactory
{
    public function __construct(private User $user, private string $type)
    {
    }

    /**
     * @return VerifyAccountAttribute
     * @throws UnsupportedVerificationService
     */
    private function bootVerifier(): VerifyAccountAttribute
    {
        $type = $this->sanitizeType();

        if ($type ==='phone') {
            return new VerifyEmailAddress($this->user, $type);
        } elseif ($type === 'email') {
            return new VerifyPhoneNumber($this->user, $type);
        } else throw new UnsupportedVerificationService("{$type} verification service is not currently supported.");
    }

    #[Pure] private function sanitizeType(): string
    {
        return Str::lower(trim($this->type));
    }
}
