<?php

namespace App\Services\TwoFactorAuth;

use App\Enums\TwoFactorAuthMethodEnum;
use App\Modules\User\Models\TwoFactorAuthentication;
use App\Modules\User\Models\TwoFactorAuthMethod;
use App\Services\Auth\Enums\NotificationType;
use App\Services\TwoFactorAuth\Exceptions\EmptyPhoneNumberException;
use App\Services\TwoFactorAuth\Exceptions\TwoFactorAuthException;
use App\Services\TwoFactorAuth\Notifications\SendTwoFactorOtpCode;
use App\Services\TwoFactorAuth\Notifications\TwoFactorAuthenticationMethodDisabled;
use App\Services\TwoFactorAuth\Notifications\TwoFactorAuthenticationMethodEnabled;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait TwoFactorAuthService
{
    /** This connects the current user to the Two-factor authentication model
     * @return HasOne
     */
    public function twoFactorAuth(): HasOne
    {
        return $this->hasOne(TwoFactorAuthentication::class)
            ->withDefault(static function (TwoFactorAuthentication $model): TwoFactorAuthentication {
                return $model->fill(config('two-factor.totp'));
            });
    }

    /**
     * First step to initialize a 2-factor authentication method
     * @param TwoFactorAuthMethod $twoFactorAuthMethod
     * @return array
     * @throws EmptyPhoneNumberException
     */
    public function initializeTwoFactorAuth(TwoFactorAuthMethod $twoFactorAuthMethod): array
    {
        $this->setTwoFactorAuth();

        $data = [];

        if ($twoFactorAuthMethod->name == TwoFactorAuthMethodEnum::Google2Fa->value) {
            $message = 'To continue, open up your Authenticator app and issue your 2FA code.';
            $data = [
                'qr_code' => $this->twoFactorAuth->toQr(),
                'secret' => $this->twoFactorAuth->toString()
            ];

        } else {
            $message = $this->generateTwoFactorCode($twoFactorAuthMethod, $twoFactorAuthMethod->ref, 'enable');
        }

        return [
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Enable a two-factor authentication method
     * @param TwoFactorAuthMethod $twoFactorAuthMethod
     * @return Collection|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function enableTwoFactorAuth(TwoFactorAuthMethod $twoFactorAuthMethod): ?Collection
    {
        $this->twoFactorAuthMethods()->attach($twoFactorAuthMethod);
        $this->verifiedAttribute($twoFactorAuthMethod->name);
        $recoveryCodes = null;

        if ($this->twoFactorAuthMethods->count() == 1) {
            $recoveryCodes = $this->generateRecoveryCodes();
        }
        $this->notify(new TwoFactorAuthenticationMethodEnabled($twoFactorAuthMethod->display_name, site()));

        return $recoveryCodes;
    }

    /**
     * disable a two-factor authentication method
     * @param TwoFactorAuthMethod $twoFactorAuthMethod
     * @return void
     */
    public function disableTwoFactorAuth(TwoFactorAuthMethod $twoFactorAuthMethod): void
    {
        $this->twoFactorAuthMethods()->detach($twoFactorAuthMethod);

        if ($this->twoFactorAuthMethods->count() == 0) {
            $this->twoFactorAuth->flushAuth()->save();
        }

        // Flushes all authentication data when the method uses an authentication app
        if ($twoFactorAuthMethod->name == TwoFactorAuthMethodEnum::Google2Fa->value) {
            $this->twoFactorAuth->flushSharedSecret()->save();
        }

        $this->notify(new TwoFactorAuthenticationMethodDisabled($twoFactorAuthMethod->display_name, site()));
    }

    /**
     * generate a two-factor code and notify the user based on a specific channel
     * @param $method
     * @param string $methodRef
     * @param string $action
     * @return string
     * @throws EmptyPhoneNumberException
     * @throws TwoFactorAuthException
     */
    public function generateTwoFactorCode($method, string $methodRef = '', string $action = 'otp'): string
    {
        $this->setTwoFactorAuth();
        $methodName = $method->name;

        if ($methodName == TwoFactorAuthMethodEnum::Email2Fa->value) {
            $message = 'A 6 digits code has been sent to your email';
        } elseif ($methodName == TwoFactorAuthMethodEnum::Sms2Fa->value) {
            if (!$this->phone) {
                throw new EmptyPhoneNumberException('Please update your profile with your phone number');
            }
            $message = 'A 6 digits code has been sent to your phone number';
        } else {
            throw new TwoFactorAuthException('Unsupported method');
        }

        $code = $this->twoFactorAuth->makeCode(methodRef: $methodRef);

        $this->notify(new SendTwoFactorOtpCode($method, $code, $action));

        return $message;
    }

    /**
     * Two factor code validation
     * @param string $code
     * @param string $methodRef
     * @param bool $useRecoveryCodes
     * @return bool
     */
    public function validateTwoFactorCode(string $code, string $methodRef = '', bool $useRecoveryCodes = true): bool
    {
        $this->setTwoFactorAuth();

        return ($this->validateCode($code, methodRef: $methodRef) ||
            ($useRecoveryCodes && $this->useRecoveryCode($code)));
    }


    /**
     * Generates a new set of Recovery Codes.
     *
     * @return Collection
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function generateRecoveryCodes(): Collection
    {
        [
            'two-factor.recovery.codes' => $amount,
            'two-factor.recovery.length' => $length
        ] = config()->get([
            'two-factor.recovery.codes', 'two-factor.recovery.length',
        ]);

        $this->twoFactorAuth->seconds = config('two-factor.totp.seconds');
        $this->twoFactorAuth->recovery_codes = TwoFactorAuthentication::generateRecoveryCodes($amount, $length);
        $this->twoFactorAuth->recovery_codes_generated_at = now();
        $this->twoFactorAuth->save();

        return $this->twoFactorAuth->recovery_codes;
    }

    /**
     * Verifies the Code against the Shared Secret.
     *
     * @param string|int $code
     * @param string $methodRef
     * @return bool
     */
    protected function validateCode(string|int $code, string $methodRef = ''): bool
    {
        return $this->twoFactorAuth->validateCode($code, methodRef: $methodRef);
    }

    /**
     * Uses a one-time Recovery Code if there is one available.
     *
     * @param string $code
     * @return mixed
     */
    protected function useRecoveryCode(string $code): bool
    {
        if (!$this->twoFactorAuth->setRecoveryCodeAsUsed($code)) {
            return false;
        }

        $this->twoFactorAuth->save();

        return true;
    }

    /**
     * Returns the label for OTP URI
     * @return string
     */
    protected function twoFactorLabel(): string
    {
        return  $this->getAttribute('email');
    }

    /**
     * @param $method
     * @return void
     */
    private function verifiedAttribute($method): void
    {
        if (TwoFactorAuthMethodEnum::Email2Fa->value == $method) {
            if ($this->email_verified_at != null) {
                $this->email_verified_at = now();
                $this->save();
            }

        } elseif (TwoFactorAuthMethodEnum::Sms2Fa->value == $method) {
            if ($this->phone_verified_at != null) {
                $this->phone_verified_at = now();
                $this->save();
            }
        }
    }

    private function setTwoFactorAuth(): void
    {
        if (!$this->twoFactorAuth->shared_secret) {
            $this->twoFactorAuth->flushAuth()->forceFill([
                'label' => $this->twoFactorLabel()
            ])->save();
        }
    }

    /**
     * Return all the Safe Devices that bypass Two-Factor Authentication.
     * @return Collection
     */
    public function safeDevices(): Collection
    {
        return $this->twoFactorAuth->safeDevices();
    }

    /**
     * @return bool
     */
    public function isSafeDevice(): bool
    {
        return $this->twoFactorAuth->isSafeDevice();
    }

    /**
     * @return void
     */
    public function addSafeDevice(): void
    {
        $this->twoFactorAuth->addSafeDevice();
    }

    /**
     * Generate a temporal token valid for 1 hour
     * @return string
     */
    public function generateTwoFactorToken(): string
    {
        return $this->twoFactorAuth->generateTwoFactorToken();
    }

    /**
     *  Validates a given token
     * @param $token
     * @return bool
     */
    public function validTwoFactorToken($token): bool
    {
        return $this->twoFactorAuth->validTwoFactorToken($token);
    }

}
