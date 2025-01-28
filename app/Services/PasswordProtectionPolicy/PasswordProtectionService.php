<?php

namespace App\Services\PasswordProtectionPolicy;

use App\Services\PasswordProtectionPolicy\Rules\DoesNotContainElementsInNames;
use App\Services\PasswordProtectionPolicy\Rules\NotInRecentPasswordHistory;
use App\Services\PasswordProtectionPolicy\Contracts\KeepPasswordHistory;
use Illuminate\Validation\Rules\Password;

class PasswordProtectionService
{
    /**
     * @var mixed|\Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|int
     */
    protected mixed $passwordLength;

    public function __construct(protected ?KeepPasswordHistory $keepPasswordHistory = null)
    {
        $this->passwordLength = config('passwordprotectionpolicy.min_password_length') ?: 8;
    }

    /**
     * @param KeepPasswordHistory $value
     * @return void
     */
    public function setModel(KeepPasswordHistory $value): void
    {
        $this->keepPasswordHistory = $value;
    }

    /**
     * @return void
     */
    public function defaultRules()
    {
        Password::defaults(function () {
            $rule = Password::min($this->passwordLength)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised();

            return $this->isAuthProtectedRoute()
                ? $rule->rules([
                    new NotInRecentPasswordHistory(
                        $this->keepPasswordHistory
                        ?? request()->user()
                    ),
                    new DoesNotContainElementsInNames(
                        $this->keepPasswordHistory
                        ?? request()->user()
                    )
                ])
                : $rule;
        });
    }

    /**
     * check if route is has auth middleware
     * @return bool
     */
    protected function isAuthProtectedRoute(): bool
    {
        return in_array('auth:api', request()->route()->gatherMiddleware());
    }
}
