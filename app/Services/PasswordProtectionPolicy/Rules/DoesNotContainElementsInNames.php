<?php

namespace App\Services\PasswordProtectionPolicy\Rules;

use App\Services\PasswordProtectionPolicy\Contracts\KeepPasswordHistory;
use Illuminate\Contracts\Validation\Rule;

class DoesNotContainElementsInNames implements Rule
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
        return $this->keepPasswordHistory->doesNotContainElementsInNames($value);
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return 'This :attribute contains your username/name.';
    }
}
