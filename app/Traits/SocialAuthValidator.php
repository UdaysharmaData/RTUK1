<?php

namespace App\Traits;

use App\Enums\SocialPlatformEnum;
use App\Services\SocialiteMultiTenancySupport\Facades\SocialitePlus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthValidator
{
    /**
     * @var mixed|null
     */
    public mixed $user = null;

    /**
     * @param array $data
     * @return bool
     */
    public function validateSocialAuthToken(array $data): bool
    {
        try {
            $socialUser = SocialitePlus::driver($data['social_auth']['source'])
                ->stateless()
                ->userFromToken($data['social_auth']['token']);

            if (! is_null($socialUser)) $this->user = $socialUser;

            if ((! is_null($email = $socialUser->getEmail())) &&
                (! is_null($user = $this->guard()
                    ->getProvider()
                    ->retrieveByCredentials([$this->username() => $email])))
            ) {
                $this->guard()->setUser($user);

                return true;
            }

            return false;
        } catch (\Exception $exception) {
            Log::error($exception);

            return false;
        }
    }

    /**
     * @return array
     */
    public function socialAuthRules(): array
    {
        return [
            'social_auth' => ['sometimes', 'array'],
            'social_auth.source' => ['required_with:social_auth', 'string', new Enum(SocialPlatformEnum::class)],
            'social_auth.token' => ['required_with:social_auth', 'string'],
        ];
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard(): \Illuminate\Contracts\Auth\StatefulGuard
    {
        return Auth::guard();
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username(): string
    {
        return 'email';
    }
}
