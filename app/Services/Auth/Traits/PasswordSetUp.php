<?php

namespace App\Services\Auth\Traits;

use App\Enums\VerificationCodeTypeEnum;
use App\Modules\User\Models\User;
use App\Services\Auth\Exceptions\ExpiredCodeException;
use App\Services\Auth\Exceptions\InvalidCodeException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

trait PasswordSetUp
{
    /**
     * Account Password Set up
     *
     * Allow user set up a password for thier account.
     *
     * @group Authentication
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @bodyParam email string required The user email. Example: user@email.com
     * @bodyParam code string required The verification code received by User. Example: 176814
     * @bodyParam password string required The password of the User. Example: 123&kPASSWORD
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws InvalidCodeException
     * @throws ExpiredCodeException
     */
    public function setup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'code' => ['required', 'string', function($attribute, $value, $fail) use ($request) {
                $user = User::query()->where('email', $request->email)->first();

                if (is_null($user)) {
                    $fail('The code you provided is invalid.');
                } else {
                    $codesQuery = $user->verificationCodes()
                        ->where('type', VerificationCodeTypeEnum::PasswordSetup->value);

                    if (
                        $codesQuery->doesntExist()
                        || $codesQuery->clone()->where('code', $value)->doesntExist()
                    ) {
                        $fail('The code you provided is invalid.');
                    } else {
                        $latest = $codesQuery->latest()->first();

                        if (! $latest->is_active) {
                            $fail('The code you provided is no longer active.');
                        }

                        if ($latest->hasExpired()) {
                            $fail('The code you provided has expired.');
                        }

                        $match = $codesQuery->firstWhere('code', $value);

                        if (is_null($match)) {
                            $fail('The code you provided is invalid.');
                        } else {
                            $this->match = $match;
                            if ($match->isNot($latest)) {
                                $fail('The code you provided is invalid. Please use the most recent code you received.');
                            }
                        }
                    }
                }
            }],
            'password' => ['required', Password::defaults()],
        ]);

        try {
            $user = User::query()->where('email', $request->email)->first();

            if ($user->hasSetPassword() && $user->temp_pass != 1) {
                return new JsonResponse(['message' => 'Your account password is already set up.'], 200);
            }

            DB::transaction(function () use ($user, $validated) {
                $this->match->update(['is_active' => false]);

                $user->update([
                    'password' => Hash::make($validated['password']),
                ]);

                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }
            });

            return new JsonResponse([
                'status' => true,
                'message' => 'Your account password has been set up.',
                'data' => [
                    'user' => $user,
                    'token' => $user->createToken($request->fingerprint()),
                    'two_factor_auth_methods' => null
                ],
            ], 200);
        } catch (\Exception $exception) {
            Log::error($exception);

            return new JsonResponse([
                'message' => 'An error occurred while setting up your account password.'
            ], 500);
        }
    }

    /**
     * Resend Password Set up Code
     *
     * Resend code to user.
     *
     * @group Authentication
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @bodyParam email string required The user email. Example: user@email.com
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resendSetupCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user->hasSetPassword()) {
            return new JsonResponse(['message' => 'You\'ve already set up a password for your account. No further action is required.'], 200);
        }

        $user->sendPasswordSetupNotification(true);

        return new JsonResponse(['message' => 'A new password set up code has been sent to your email.'], 200);
    }
}
