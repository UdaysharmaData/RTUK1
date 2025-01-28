<?php

namespace App\Services\Auth;

use App\Enums\VerificationCodeTypeEnum;
use App\Modules\User\Models\User;
use App\Services\Auth\Contracts\VerifiableInterface;
use App\Services\Auth\Exceptions\AlreadyVerifiedAttribute;
use App\Services\Auth\Exceptions\InvalidVerifiableAttribute;
use App\Services\Auth\Traits\UserAccountVerifiableTraits;
use App\Traits\Response;
use Exception;
use Illuminate\Database\Eloquent\Model;

abstract class VerifyAccountAttribute implements VerifiableInterface
{
    use UserAccountVerifiableTraits, Response;

    /**
     * @var array
     */
    protected array $validationConditions = [];

    /**
     * @param User $user
     * @param string $attribute
     */
    public function __construct(public User $user, public string $attribute)
    {
    }

    /**
     * @return mixed
     * @throws Exception
     */
    abstract public function sendVerificationCode(): void;

    /**
     * @return $this
     */
    abstract public function setValidationConditions(): static;

    /**
     * @return Model
     * @throws Exception
     */
    protected function generateCode(): Model
    {
        return $this->user->verificationCodes()->create([
            'type' => VerificationCodeTypeEnum::AccountVerification->value
        ]);
    }

    /**
     * @return void
     */
    public function checkValidationConditions()
    {
        foreach ($this->validationConditions as $condition) {
            if (! $condition->isPassed()) {
                $condition->handleIfConditionFails();
            }
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function attemptVerification(): void
    {
        try {
            $this->setValidationConditions()
                ->checkValidationConditions();

            $this->sendVerificationCode();

//            return $this->success([], "Code has been sent to your {$this->attribute}");

        } catch (InvalidVerifiableAttribute|AlreadyVerifiedAttribute $exception) {
//            return $this->error($exception->getMessage(), 501);
        }
    }
}
