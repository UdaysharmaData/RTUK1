<?php

namespace App\Services\Auth\Services;

use Closure;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Auth\PasswordBroker as PasswordBrokerContract;
use Illuminate\Contracts\Auth\UserProvider;
use JetBrains\PhpStorm\Pure;

class ApiPasswordBroker extends PasswordBroker implements PasswordBrokerContract
{
    #[Pure] public function __construct(TokenRepositoryInterface $tokens, UserProvider $users)
    {
        parent::__construct($tokens, $users);
    }

    /**
     * Send a password reset link to a user.
     *
     * @param  array  $credentials
     * @param  Closure|null  $callback
     * @return string|array
     */
    public function sendResetLink(array $credentials, Closure $callback = null): string|array
    {
        // First we will check to see if we found a user at the given credentials and
        // if we did not we will redirect back to this current URI with a piece of
        // "flash" data in the session to indicate to the developers the errors.
        $user = $this->getUser($credentials);

        if (is_null($user)) {
            return static::INVALID_USER;
        }

        if ($this->tokens->recentlyCreatedToken($user)) {
            return static::RESET_THROTTLED;
        }

        $token = $this->tokens->create($user);

        if ($callback) {
            $callback($user, $token);
        } else {
            // Once we have the reset token, we are ready to send the message out to this
            // user with a link to reset their password. We will then redirect back to
            // the current URI having nothing set in the session to indicate errors.
            $user->sendPasswordResetNotification($token);
        }

        return [
            'status' => static::RESET_LINK_SENT,
            'token' => $token
        ];
    }
}
