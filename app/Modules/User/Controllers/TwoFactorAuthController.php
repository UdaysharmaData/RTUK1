<?php

namespace App\Modules\User\Controllers;

use App\Enums\TwoFactorAuthMethodEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\TwoFactorAuthMethodResource;
use App\Modules\User\Models\TwoFactorAuthMethod;
use App\Modules\User\Requests\TwoFactorCodeRequest;
use App\Services\TwoFactorAuth\Exceptions\TwoFactorAuthException;
use App\Services\TwoFactorAuth\Exceptions\TwoFactorMethodNotEnableException;
use App\Services\TwoFactorAuth\Rules\VerifyTwoFactorCode;
use App\Traits\Response;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorAuthController extends Controller
{
    use Response;

    public function __construct()
    {
        $this->middleware('throttle:60,2')->except('index');

    }

    /**
     * List of 2fa methods
     *
     * Get all actives 2fa methods available in the platform.
     *
     * @group Two-factor authentication
     * @authenticated
     * @header Content-Type application/json
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return $this->success(
            'List of two-factor auth methods available',
            200,
            ['two_factor_auth_methods' => TwoFactorAuthMethodResource::collection(TwoFactorAuthMethod::Active()->get())]
        );
    }


    /**
     * Enable a 2fa method
     *
     * User enables a 2fa method, and it's done in two steps. When a user enables a 2fa method for the first time,
     * we generate recovery codes a list of 10 codes of 8 characters that can be used once to bypass the 2fa security in case
     * the user loses his phone. Endpoints where the 2fa are applied for now: Login, Password Update
     *
     * Step 1: We initialize the 2fa depending on the method chosen by the user. For the
     * sms & email case, an OTP code will be sent to the user via a specific driver (sms or mail), and for the Google
     * authentication case a QR code will be generated
     *
     * Step 2: We validate the OTP code entered by the user and enable the 2fa method.
     *
     * @urlParam method_ref string required specifies 2fa method ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     * @queryParam step string required specifying the step. Example: 1 or 2
     * @bodyParam totp_code string optional The Totp code is only required at step 2 which is used to validate
     * the otp code and enable the 2fa method. Example: 675899
     *
     * @group Two-factor authentication
     * @param Request $request
     * @param TwoFactorAuthMethod $method
     * @return JsonResponse
     * @throws Exception
     */
    public function enableTwoFactorAuth(Request $request, TwoFactorAuthMethod $method): JsonResponse
    {
        if ($method->isEnabledBy($request->user())) {
            return $this->error(
                'Two-factor authentication method has already been enabled',
                406
            );
        }

        if ($request->get('step') == 1) {
            $result = $request->user()->initializeTwoFactorAuth($method);
            $message = $result['message'];
            $data = $result['data'];

        } else if ($request->get('step') == 2) {
            $methodRef = $method->name == TwoFactorAuthMethodEnum::Google2Fa->value ? '' : $method->ref;
            $request->validate(['totp_code' => [new VerifyTwoFactorCode(methodRef: $methodRef, useRecoveryCodes: false)]]);

            $recoveryCodes = $request->user()->enableTwoFactorAuth($method);
            $message = 'Two factor authentication method has been successfully activated';
            $data = ['recovery_codes' => $recoveryCodes];

        } else {
            throw new TwoFactorAuthException('Unknown operation');
        }

        return $this->success(
            $message,
            200,
            $data
        );
    }

    /**
     * Disable a 2fa method
     *
     * User disable a 2fa method, and it's done in 2 steps:
     *
     * Step 1: We sent an OTP code for sms and email verification,and for Google auth method the user generate
     * an otp code from the authentication app
     *
     * Step 2: We validate the OTP code entered by the user, and disable the method.
     *
     * @urlParam method_ref string required specifies 2fa method ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     * @queryParam step string required specifying the step. Example: 1 or 2
     * @bodyParam totp_code string optional The Totp code is only required at step 2 which is used to validate
     * the otp code or recovery code and disable the 2fa method. Example: 123456 or ABCDEFGHI(recovery code)
     *
     * @group Two-factor authentication
     * @param TwoFactorCodeRequest $request
     * @param TwoFactorAuthMethod $method
     * @return JsonResponse
     * @throws TwoFactorMethodNotEnableException
     * @throws Exception
     */
    public function disableTwoFactorAuth(Request $request, TwoFactorAuthMethod $method): JsonResponse
    {
        $this->checkIf2faMethodIsEnabled($request, $method);

        if ($request->get('step') == 1) {

            if ($method->name == TwoFactorAuthMethodEnum::Google2Fa->value) {
                $message = 'Use your authentication app to generate the OTP code';

            } else {
                $message = $request->user()->generateTwoFactorCode($method, $method->ref, 'disable');
            }

        } else if ($request->get('step') == 2) {
            $methodRef = $method->name == TwoFactorAuthMethodEnum::Google2Fa->value ? '' : $method->ref;
            $request->validate(['totp_code' => [new VerifyTwoFactorCode(methodRef: $methodRef)]]);

            $request->user()->disableTwoFactorAuth($method);
            $message = 'Two factor authentication method has been successfully disabled';

        } else {
            throw new TwoFactorAuthException('Unknown operation');
        }

        return $this->success(
            $message,
        );
    }

    /**
     * Send 2fa code
     *
     * Use this endpoint to send an otp code in case the user has enabled a 2fa method(Only Email or Sms verification method).
     * It can be used if a route required a 2fa such as the Login endpoint
     *
     * @urlParam method_ref string required specifies 2fa method ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @group Two-factor authentication
     * @throws TwoFactorMethodNotEnableException
     */
    public function sentOtpCode(Request $request, TwoFactorAuthMethod $method): JsonResponse
    {
        $this->checkIf2faMethodIsEnabled($request, $method);

        $message = $request->user()->generateTwoFactorCode($method);

        return $this->success($message);
    }

    /**
     * Generate a 2fa token
     *
     * Generate a temporal token that will be used by certain endpoint in case the 2fa method has been enabled by the user.
     * For now the token will be used when the user want to update his password and generate new recovery codes.
     *
     * @bodyParam totp_code string required The Totp code received by mail, sms or generate by the authenticator app.
     * depending on the 2fa method chosen by the user. The user can also use the recovery code.
     * Example: 675899 or APXAEFENNM(recovery code)
     *
     * @group Two-factor authentication
     * @param Request $request
     * @return JsonResponse
     */
    public function generateTwoFactorToken(Request $request): JsonResponse
    {
        $request->validate(['totp_code' => [new VerifyTwoFactorCode()]]);
        $token = $request->user()->generateTwoFactorToken();

        return $this->success(
            'Two factor token has been successfully generated',
            200,
            ['totp_token' => $token]
        );
    }

    /**
     * Renew recovery codes
     *
     * we generate recovery codes a list of 10 codes of 8 characters that can be used once to bypass the 2fa security in case
     * the user lost his phone.
     *
     * @bodyParam totp_token string required
     * Example: e38ceddc464281b6205191473388d8787270f070
     *
     * @group Two-factor authentication
     * @param Request $request
     * @return JsonResponse
     */
    public function generateRecoveryCodes(Request $request): JsonResponse
    {
        $recoveryCodes = $request->user()->generateRecoveryCodes();

        return $this->success(
            'Recovery codes has been successfully generated',
            200,
            ['recovery_codes' => $recoveryCodes]

        );
    }

    /**
     * Mark a 2fa method as default
     *
     * When a user has more than one method enabled, he can set one as the default
     *
     * @urlParam method_ref string required specifies 2fa method ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     * @group Two-factor authentication
     *
     * @throws TwoFactorMethodNotEnableException
     */
    public function markAsDefault(Request $request, TwoFactorAuthMethod $method): JsonResponse
    {
       $this->checkIf2faMethodIsEnabled($request, $method);

        $request->user()->twoFactorAuthUsers()->where('two_factor_auth_method_id', $method->id)
           ->update(['default' => true]);

        $request->user()->twoFactorAuthUsers()->where('two_factor_auth_method_id', '!=', $method->id)
           ->update(['default' => false]);

       return $this->success(
           'The two factor authentication method has been successfully marked as default',
           200,
           ['two_factor_auth_methods' => TwoFactorAuthMethodResource::collection(TwoFactorAuthMethod::Active()->get())]
       );
    }

    /**
     * 2fa token validity
     *
     * Check if the 2fa token is valid
     *
     * @bodyParam totp_token string required The 2fa token generated by the user.
     * Example: e38ceddc464281b6205191473388d8787270f070
     *
     * @group Two-factor authentication
     * @return JsonResponse
     */
    public function twoFactorTokenValidity(): JsonResponse
    {
        return $this->success('2fa token is valid');
    }



    /**
     * check if the 2fa method chosen by the user is enabled
     * @param Request $request
     * @param TwoFactorAuthMethod $method
     * @return void
     * @throws TwoFactorMethodNotEnableException
     */
    private function checkIf2faMethodIsEnabled(Request $request, TwoFactorAuthMethod $method): void
    {
        if (!$method->isEnabledBy($request->user())) {
            throw new TwoFactorMethodNotEnableException('Two factor authentication method is not enabled');
        }
    }

}
