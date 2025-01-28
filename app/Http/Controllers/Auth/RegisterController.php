<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleNameEnum;
use App\Http\Controllers\Controller;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use App\Providers\RouteServiceProvider;
use App\Rules\UniqueToSite;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;
use App\Traits\Response;
use App\Traits\SocialAuthValidator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers, Response;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected string $redirectTo = RouteServiceProvider::HOME;
    /**
     * @var true
     */
    private bool $existingUserLinkedToSite = false;
    /**
     * @var array
     */
    private array $data;
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
        $this->middleware('guest');
        $this->socialValidator = new SocialAuthValidator();
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        $this->data = $data;

        $validator = Validator::make($data, [
            'first_name' => ['required_without:social_auth', 'string', 'max:255'],
            'last_name' => ['required_without:social_auth', 'string', 'max:255'],
            'dob' => ['sometimes', 'date', 'date_format:Y-m-d', 'before:today'],
            'email' => [
                'required_without:social_auth',
                new UniqueToSite,
                'string',
                'email',
                'max:255',
            ],
            'phone' => ['required_without:social_auth', 'string', 'max:14'],
            'password' => ['required_without:social_auth', Password::defaults()],
            ...$this->socialValidator->socialAuthRules(),
//            'temp_pass' => ['nullable', 'boolean'],
//            'participant_authorised' => ['nullable', 'boolean']
        ]);

        if (isset($this->data['social_auth'])) {
            $validator->after(function ($validator) use ($data) {
                if (! $this->socialValidator->validateSocialAuthToken($data)) {
                    $validator->errors()->add(
                        'social_auth.token', 'Invalid social auth token.'
                    );
                }
            });
        }

        return $validator;
    }

    /**
     * @return array|null
     */
    private function getUserDataFromProvider(): ?array
    {
        if (
            isset($this->socialValidator->user)
            && (($providerUser = $this->socialValidator->user) instanceof \Laravel\Socialite\Contracts\User)
        ) {
            $names = explode(' ', $providerUser->getName());

            return [
                'email' => $providerUser->getEmail(),
                'first_name' => $names[0],
                'last_name' => $names[1],
                'nickname' => $providerUser->getNickname(),
                'avatar' => $providerUser->getAvatar()
            ];
        }

        return null;
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return User
     */
    protected function create(array $data): User
    {
        return DB::transaction(function () use($data) {
            $profileData = $this->filterDataByKeys($data, ['dob']);
            if (isset($this->data['social_auth']) && (is_null($this->getUserDataFromProvider()))) {
                throw new \RuntimeException('Unable to retrieve user data from provider.');
            }

            if (isset($this->data['social_auth']) && (! is_null($providerData = $this->getUserDataFromProvider()))) {
                $email = $providerData['email'];
            } else {
                $email = $data['email'];
            }

            if (! is_null($user = User::where('email', $email)->first())) {
                $user->sites()->syncWithoutDetaching([clientSiteId()]);
                $this->existingUserLinkedToSite = true;

                if ($user->roles->isEmpty()) {
                    if (! is_null($id = Role::firstWhere('name', '=', RoleNameEnum::Participant?->value)?->id)) {
                        $user->syncRolesOnCurrentSite([$id], false);
                    }
                }

                if (is_null($user->activeRole)) {
                    $user->assignDefaultActiveRole();
                }

                CacheDataManager::flushAllCachedServiceListings(new UserDataService);

                return $user;
            }

            $data = isset($this->data['social_auth']) && (! is_null($providerData = $this->getUserDataFromProvider()))
                ? array_filter(
                    $providerData,
                    fn ($key) => in_array($key, ['first_name', 'last_name', 'email']),
                    ARRAY_FILTER_USE_KEY
                ) : [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password' => isset($data['password']) ? Hash::make($data['password']) : null,
//                  'temp_pass' => $data['temp_pass'],
//                  'participant_authorised' => $data['participant_authorised']
                ];

                $user = User::create($data);
                if(isset($profileData) && !empty($profileData)){
                    $this->createOrUpdateUserProfile($user, $profileData);
                }
                return $user;

        });
    }

    /**
     * Create a user
     *
     * Handle a registration request for the application.
     *
     * @group Authentication
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @bodyParam first_name string required The first name of the user. Example: Wendy
     * @bodyParam last_name string required The last name of the user. Example: Mike
     * @bodyParam email string required The email of the User. Example: user@email.com
     * @bodyParam phone string required The phone number of the User. Example: +12333333333
     * @bodyParam password string required The password of the User. Example: 123@!PASSWORD
     * @bodyParam social_auth array optional The social_auth is required only if the user is trying to register with a social account.
     * @bodyParam social_auth.source string required_with:social_auth The social_auth.source is required only if the user is trying to register with a social account. Example: facebook
     * @bodyParam social_auth.token string required_with:social_auth The social_auth.token is required only if the user is trying to register with a social account. Example: 123456 or ZADFGVDS(recovery code)
     *
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function register(Request $request): mixed
    {
        $this->validator($request->all())->validate();

        try {
            if (! $this->existingUserLinkedToSite) {
                event(new Registered($user = $this->create($request->all())));
            }

            if ($response = $this->registered($request, $user)) {
                return $response;
            }

            return $request->wantsJson()
                ? new JsonResponse([], 201)
                : redirect($this->redirectPath());
        } catch (\Exception $exception) {
            return $this->error('An error occurred while creating user account. Please try again in a bit.', 500);
        }
    }

    /**
     * The user has been registered.
     *
     * @param Request $request
     * @param mixed $user
     * @return JsonResponse
     */
    protected function registered(Request $request, $user): JsonResponse
    {
        $fingerPrint = $request->fingerprint();
        $accessGrant = $user->createToken($fingerPrint);

        return $this->success($this->existingUserLinkedToSite ? 'Your account has been linked to the platform!' : 'Your registration was successful!', 201, [
            'user' => $user->load(['activeRole', 'roles', 'profile']),
            'token' => $accessGrant->accessToken,
        ]);
    }

        /**
     * @param User $user
     * @param mixed $data
     * @return \Illuminate\Database\Eloquent\Model|CanHaveUploadableResource
    */
    private function createOrUpdateUserProfile(User $user, mixed $data): \Illuminate\Database\Eloquent\Model|CanHaveUploadableResource
    {
        return $user->profile()->updateOrCreate([
            'dob' => $data['dob']
        ]);
    }
 
        /**
     * @param array $data
     * @param array $filters
     * @return array
     */
    protected function filterDataByKeys(array $data, array $filters = []): array
    {
        return array_filter(
            $data,
            fn($key) => in_array($key, $filters),
            ARRAY_FILTER_USE_KEY
        );
    }


}
