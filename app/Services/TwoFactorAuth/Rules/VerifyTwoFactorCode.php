<?php

namespace App\Services\TwoFactorAuth\Rules;

use App\Modules\User\Models\User;
use Illuminate\Contracts\Validation\Rule;

class VerifyTwoFactorCode implements Rule
{
    /**
     * @var User
     */
    private User $user;

    /**
     * @var string
     */
    private string $methodRef;

    /**
     * @var bool|mixed
     */
    private bool $useRecoveryCodes;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($user = null, $methodRef = '', $useRecoveryCodes = true)
    {
        $this->user = $user ?: request()->user();
        $this->methodRef = $methodRef;
        $this->useRecoveryCodes = $useRecoveryCodes;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return $value && is_string($value)
            && $this->user->validateTwoFactorCode($value, $this->methodRef, $this->useRecoveryCodes);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The code is invalid or has expired';
    }
}
