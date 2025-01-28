<?php

namespace App\Services\Auth\Rules\ValidCode;

use App\Enums\VerificationCodeTypeEnum;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Validation\Rule;

class ValidCode implements Rule
{
    private string $message;
    /**
     * @var string|mixed
     */
    private mixed $identifier;

    public function __construct(string $identifier)
    {
        if ($requestUser = request()->user()) {
            $this->identifier = $requestUser;
        } else {
            $user = User::where('email', $identifier)->first();
            if (! is_null($user)) {
                $this->identifier = $user;
            } else abort(422, 'Email address not recognized.');
        }
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $code = $this->identifier->verificationCodes()
            ->where('code', $value)
            ->where('type', VerificationCodeTypeEnum::PasswordReset->value)
            ->first();

        if (is_null($code)) {
            $this->message = 'The code is invalid.';
            return false;
        }

        if (! $code->is_active) {
            $this->message = 'The code is no longer active.';
            return false;
        }

        if ($code->hasExpired()) {
            $this->message = 'The code has expired.';
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}
