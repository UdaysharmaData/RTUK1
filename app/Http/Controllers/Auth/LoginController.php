<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ErrorResponseCode;
use App\Enums\RoleNameEnum;
use App\Enums\SocialPlatformEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\TwoFactorAuthMethodResource;
use App\Modules\User\Models\ActiveRole;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;
use App\Services\SocialiteMultiTenancySupport\Facades\SocialitePlus;
use App\Services\TwoFactorAuth\Rules\VerifyTwoFactorCode;
use App\Traits\Response;
use App\Traits\SocialAuthValidator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers, Response;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;
    /**
     * @var SocialAuthValidator
     */
    private SocialAuthValidator $socialValidator;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        $this->middleware('guest')->except('logout');
        $this->socialValidator = new SocialAuthValidator();
    }

    /**
     * Login
     *
     * Handle a login request to the application. Below are the logins of users with different roles. PS: All the users have a common password which is "password".
     *
     * Administrator: Mark@runforcharity.com
     *
     * Developer: developer@wearedamage.com
     *
     * Account Manager: marc@runforcharity.com
     *
     * Account Manager: freddie@runforcharity.com [WWF]
     *
     * Charity: teampanda@wwf.org.uk [WWF]
     *
     * Charity User: Joemcdermott@macmillan.org.uk [Macmillan Cancer Support]
     *
     * Event Manager: matt@runthrough.co.uk
     *
     * Participant: norberth.t@gmail.com
     *
     * Participant: fharle_88@hotmail.co.uk
     *
     * @group Authentication
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @bodyParam email string required The email of the User. Example: Mark@runforcharity.com
     * @bodyParam password string required The password of the User. Example: Password.0!
     * @bodyParam totp_code string optional The totp code is required only if the user has enabled a 2fa method.
     * @bodyParam social_auth array optional The social_auth is required only if the user is trying to login with a social account.
     * @bodyParam social_auth.source string required_with:social_auth The social_auth.source is required only if the user is trying to login with a social account. Example: facebook
     * @bodyParam social_auth.token string required_with:social_auth The social_auth.token is required only if the user is trying to login with a social account. Example: 123456 or ZADFGVDS(recovery code)
     * Example: 123456 or ZADFGVDS(recovery code)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request): JsonResponse|\Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {

            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            $user = $this->guard()->user();

//            if ($user->twoFactorAuthMethods()->exists() && !$user->isSafeDevice()) {
//                $methods = TwoFactorAuthMethodResource::collection($user->twoFactorAuthMethods);
//
//                if (!$request->has('totp_code')) {
//                    return response()->json([
//                        'success' => false,
//                        'code' => ErrorResponseCode::TwoFactorRequired,
//                        'message' => 'Two-factor authentication is required',
//                        'two_factor_auth_methods' =>  $methods
//                    ], 403);
//                }
//
//                $request->validate(['totp_code' => [new VerifyTwoFactorCode($user)]]);
//                $user->addSafeDevice();
//            }

            if (is_null($user?->activeRole)) {
                DB::transaction(function () use ($user) {
                    if ($user->roles->isEmpty()) {
                        if (! is_null($id = Role::firstWhere('name', '=', RoleNameEnum::Participant?->value)?->id)) {
                            $user->syncRolesOnCurrentSite([$id], false);
                        }
                    }

                    $user->assignDefaultActiveRole();
                    $user->grantRoleDefaultPermissions();

                    if (clientSiteId()) {
                        $user->sites()->syncWithoutDetaching([clientSiteId()]);
                    }

                    CacheDataManager::flushAllCachedServiceListings(new UserDataService);
                });
            }

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }


     /**
     * Login
     *
     * Admin can log in as a user.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function login_via_website(User $user): JsonResponse|\Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        try {
            $request = request();
            $fingerPrint = $request->fingerprint();
            $accessGrant = $user->createToken($fingerPrint);

            if ($user->roles->isEmpty()) {
                if (! is_null($id = Role::firstWhere('name', '=', RoleNameEnum::Participant?->value)?->id)) {
                    $user->syncRolesOnCurrentSite([$id], false);
                    CacheDataManager::flushAllCachedServiceListings(new UserDataService);
                }
            }

            if (is_null($user->activeRole)) {
                $user->assignDefaultActiveRole();
            }

            return $this->success('Successful authentication', 200, [
                'user' => $user->withoutRelations()->load(['activeRole', 'roles', 'profile']),
                'token' => $accessGrant->accessToken,
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error("Oops...We couldn't find this user you were trying to update.", 400);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to login as user', 400);
        }
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param Request $request
     * @return bool
     */
    protected function attemptLogin(Request $request): bool
    {
        if ($request->has('social_auth')) {
            return $this->socialValidator
                ->validateSocialAuthToken($request->only(['social_auth']));
        } else {
            return $this->guard()->attempt(
                $this->credentials($request), $request->boolean('remember')
            );
        }
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request): void
    {
        $request->validate([
            $username = $this->username() => ['required_without:social_auth', 'string', "exists:users,$username"],
            'password' => ['required_without:social_auth', 'string', function ($attribute, $value, $fail) use ($request) {
                $user = $this->guard()->getProvider()->retrieveByCredentials([
                    $this->username() => $request->get($this->username()),
                ]);

                if (optional($user)->password === null) {
                    $fail('A password has not been set up.');
                }

                if (! Hash::check($value, optional($user)->password)) {
                    $fail('The password is incorrect.');
                }
            }],
            ...$this->socialValidator->socialAuthRules()
        ]);
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    protected function sendLoginResponse(Request $request): JsonResponse|RedirectResponse
    {
        $this->clearLoginAttempts($request);

        if ($response = $this->authenticated($request, $this->guard()->user())) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect()->intended($this->redirectPath());
    }

    /**
     * The user has been authenticated.
     *
     * @param Request $request
     * @param mixed $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user): mixed
    {
        try {
//            if (auth()->check()) {
//                return $this->success('Successful authentication', 200, [
//                    'user' => $user->fresh(),
//                ]);
//            }
            $fingerPrint = $request->fingerprint();
            $accessGrant = $user->createToken($fingerPrint);

            return $this->success('Successful authentication', 200, [
                'user' => $user->withoutRelations()->load(['activeRole', 'roles', 'profile']),
                'token' => $accessGrant->accessToken,
                'two_factor_auth_methods' => null
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while you were trying to login. Please try again in a bit.', 400);
        }
    }

    /**
     * Logout
     *
     * Log the user out of the application.
     *
     * @group Authentication
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam all string If the User wants to revoke all their active access tokens. Example: true
     * <aside class="notice">?all=true param essentially logs out all currently logged-in devices</aside>
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $user = $this->guard()->user();

        if ($this->forAllDevices()) {
            $user->tokens()->each(fn($token) => $token->revoke());
        } else $user->token()->revoke();

        if ($response = $this->loggedOut($request, $this->forAllDevices())) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect('/');
    }

    /**
     * @return bool
     */
    private function forAllDevices(): bool
    {
        return request()->has('all') && request('all') === 'true';
    }

    /**
     * The user has logged out of the application.
     *
     * @param Request $request
     * @param bool $logOutAll
     * @return mixed
     */
    protected function loggedOut(Request $request, bool $logOutAll): mixed
    {
        return $this->success($logOutAll
            ? 'All Authentication Tokens Revoked'
            : 'Current Authentication Token Revoked');
    }
}
