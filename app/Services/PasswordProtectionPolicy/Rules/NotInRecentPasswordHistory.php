<?php

namespace App\Services\PasswordProtectionPolicy\Rules;

use App\Services\PasswordProtectionPolicy\Contracts\KeepPasswordHistory;
use Illuminate\Contracts\Validation\Rule;

class NotInRecentPasswordHistory implements Rule
{
    /**
     * @param KeepPasswordHistory|null $keepPasswordHistory
     */
    public function __construct(protected ?KeepPasswordHistory $keepPasswordHistory)
    {
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return $this->keepPasswordHistory->notInRecentHistory($value);
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return 'This :attribute has been used by you recently.';
    }
}
