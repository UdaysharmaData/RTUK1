<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmSocialActionRequest;
use App\Modules\User\Models\User;
use App\Services\SignedExternalUrlProcessor;
use App\Services\SocialiteMultiTenancySupport\Controllers\SocialiteBaseController;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConfirmSocialActionController
{
    use Response;

    /**
     * Finalize a Socials Action
     *
     * Verify that the redirect url is valid and allow user proceed.
     *
     * @group Socials
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @bodyParam full_url string required The full URL of the redirected page after a social login/registration. Example: https://website.runthroughhub.com/socials?expires=1668415147&type=auth&user=968e6647-c7a2-49cb-ad17-b6226a20f903&signature=37b2f3e2dd259ab9d79256649c6d78e542f6f0514ddbbf1d9e368ef2478c2c8d
     *
     * @param ConfirmSocialActionRequest $request
     * @param SignedExternalUrlProcessor $signedUrlProcessor
     * @return JsonResponse
     */
    public function __invoke(ConfirmSocialActionRequest $request, SignedExternalUrlProcessor $signedUrlProcessor): JsonResponse
    {
        try {
            $queryParams = SignedExternalUrlProcessor::getQueryParameters($fullUrl = $request->validated('full_url'));

            if (isset($queryParams['error'])) {
                $errorKey = $queryParams['error'];
                $errorMessage = Cache::pull($errorKey);

                return $this->error($errorMessage, 400);
            }

            // https://portal.runthroughhub.com/auth/login?expires=1703078819&signature=25aa3cb05522bc5feffadfcbddba83201270b7fa377d2a489cd436b473d0ea7b

            if (
                isset($queryParams['expires']) && isset($queryParams['signature'])
            ) {
                $cachedData = Cache::pull(sha1($queryParams['signature'].SocialiteBaseController::getHashedRequestIdentity()));
//                $cachedData = Cache::pull(sha1($queryParams['signature']));

                if (! is_null($cachedData)) {
                    if (isset($cachedData['origin_url']) && $signedUrlProcessor->hasValidSignature($fullUrl, $cachedData['origin_url'])) {
                        $user = User::findOrFail($cachedData['user_id']);
                        $eventType = $cachedData['event_type'];
                        $fingerPrint = $request->fingerprint();
                        $accessGrant = $user->createToken($fingerPrint);

                        return $this->success('Action Verified.', 200, [
                            'social_event_type' => $eventType,
                            'user' => $user->getUserAndConnectedDevices(),
                            'token' => $accessGrant->accessToken,
                        ]);
                    } elseif ($signedUrlProcessor->hasExpired) {
                        return $this->error('This socials redirect URL has expired.', 400);
                    }
                } else {
                    Log::error('Unable to find cached data for socials redirect URL');
                }
            }

            return $this->error('Unable to verify provided redirect URL', 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to verify socials.', 400);
        }
    }
}
