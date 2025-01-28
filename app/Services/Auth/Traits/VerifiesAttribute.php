<?php

namespace App\Services\Auth\Traits;

use App\Enums\VerificationCodeTypeEnum;
use App\Services\Auth\Exceptions\ExpiredCodeException;
use App\Services\Auth\Exceptions\InvalidCodeException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\Pure;

trait VerifiesAttribute
{
    /**
     * Account Verification
     *
     * Mark the authenticated user's email address as verified.
     *
     * @group Authentication
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam type string required The verification code received by User. Example: email
     * @bodyParam code string required The verification code received by User. Example: 17681
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws InvalidCodeException
     * @throws ExpiredCodeException
     */
    public function verify(Request $request): JsonResponse
    {
        try {
            $hasVerifiedMethod = $this->hasVerifiedMethod();

            if ($request->user()->$hasVerifiedMethod()) {
                return new JsonResponse(['message' => 'Your account '. $this->attributeName . ' is already verified.'], 200);
            }

            $validated = $request->validate([
                'code' => ['required', 'string', 'digits:6'],
            ]);

            $codesQuery = $request->user()->verificationCodes()
                ->where('type', VerificationCodeTypeEnum::AccountVerification->value);

            if (
                $codesQuery->doesntExist()
                || $codesQuery->clone()->where('code', $validated['code'])->doesntExist()
            ) {
                throw new InvalidCodeException('Invalid verification code.', 422);
            }

            $latest = $codesQuery->latest()->first();

            if ($latest->hasExpired()) {
                throw new ExpiredCodeException('Expired verification code.', 422);
            }

            if (! $latest->is_active) {
                throw new InvalidCodeException('Verification code is no longer active.', 422);
            }

            $match = $codesQuery->firstWhere('code', $validated['code']);

            if ($match->isNot($latest)) {
                throw new ExpiredCodeException('This code is no longer valid. Please use the most recent code you received.', 422);
            }

            $attribute = $this->getUcfirstAttributeName();
            $hasVerifiedMethod = $this->hasVerifiedMethod();

            if ($request->user()->$hasVerifiedMethod()) {
                return new JsonResponse(['message' => 'Your account '. $this->attributeName . ' is already verified.'], 200);
            }

            $markAttributeAsVerifiedMethod = "mark{$attribute}AsVerified";

            if ($request->user()->$markAttributeAsVerifiedMethod()) {
                $match->update(['is_active' => false]);

                return new JsonResponse([
                    'message' => 'Account Verification was successful.'
                ], 200);
            }

            if ($response = $this->verified($request)) {
                return $response;
            }

            return new JsonResponse([], 204);
        } catch (InvalidCodeException | ExpiredCodeException $e) {
            return new JsonResponse([
                'message' => $e->getMessage()
            ], $e->getCode());
        } catch (\Exception $e) {
            Log::error($e);
            return new JsonResponse([
                'message' => 'Something went wrong. Please try again later.'
            ], 500);
        }
    }

    /**
     * The user has been verified.
     *
     * @param Request $request
     * @return mixed
     */
    protected function verified(Request $request): mixed
    {
        //
    }

    /**
     * Redo Verification
     *
     * Resend verification code to user.
     *
     * @group Authentication
     * @authenticated
     * @header Content-Type application/json
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resend(Request $request): JsonResponse
    {
        $hasVerifiedMethod = $this->hasVerifiedMethod();
        $user = $request->user();

        if ($user->$hasVerifiedMethod()) {
            return new JsonResponse(['message' => 'Your account has already been verified.'], 200);
        }

        $sendAttributeVerificationNotificationMethod = "send{$this->attributeName}VerificationNotification";

        $user->$sendAttributeVerificationNotificationMethod();

//        $this->invalidatePreviousCode($user);

        return new JsonResponse(['message' => 'A new verification code has been sent.'], 200);
    }

    /**
     * @return string
     */
    private function getUcfirstAttributeName(): string
    {
        return ucfirst($this->attributeName);
    }

    /**
     * @return string
     */
    #[Pure] private function hasVerifiedMethod(): string
    {
        return "hasVerified{$this->getUcfirstAttributeName()}";
    }

    /**
     * @param mixed $user
     * @return void
     */
    private function invalidatePreviousCode(mixed $user): void
    {
        $codes = $user->verificationCodes()
            ->where('type', VerificationCodeTypeEnum::AccountVerification->value)
            ->latest();

        if ($codes->exists()) {
            $codes->first()?->update(['expires_at' => now()]);
        }
    }
}
