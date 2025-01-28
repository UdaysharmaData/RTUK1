<?php

namespace App\Modules\User\Controllers;

use App\Contracts\Uploadables\CanHaveUploadableResource;
use App\Enums\GenderEnum;
use App\Enums\ListTypeEnum;
use App\Enums\RoleNameEnum;
use App\Enums\SiteUserActionEnum;
use App\Enums\SiteUserStatus;
use App\Enums\UploadTypeEnum;
use App\Enums\UploadUseAsEnum;
use App\Facades\ClientOptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\RestoreUsersRequest;
use App\Http\Requests\UserListingQueryParamsRequest;
use App\Http\Requests\DeleteUsersRequest;
use App\Modules\Setting\Models\Site;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\SiteUser;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\AddUsersToSiteRequest;
use App\Modules\User\Requests\RemoveUsersFromSiteRequest;
use App\Modules\User\Requests\StoreUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Requests\UsersActionRequest;
use App\Services\Auth\Enums\NotificationType;
use App\Services\Auth\Notifications\SendVerificationCode;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DefaultQueryParamService;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\FileManager\Traits\SingleUploadModel;
use App\Services\SoftDeleteable\Exceptions\DeletionConfirmationRequiredException;
use App\Services\SoftDeleteable\Exceptions\InvalidSignatureForHardDeletionException;
use App\Services\SoftDeleteable\SoftDeleteableManagementService;
use App\Services\DataServices\UserDataService;
use App\Services\FileManager\Traits\UploadModelTrait;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Traits\DownloadTrait;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    use Response, SingleUploadModel, DownloadTrait, UploadModelTrait;

    /**
     * @var bool
     */
    private bool $existingUserLinkedToSite = false;

    public function __construct(protected UserDataService $userService)
    {
        parent::__construct();
    }

    /**
     * Users' Listing
     *
     * Get paginated application users' list.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam role string Specifying the user role to query by. Example: administrator
     * @queryParam term string Specifying a keyword similar to first_name, last_name, phone, or email. Example: john@email
     * @queryParam per_page string Overriding the default (10) number of listings per-page. Example: 20
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: first_name:desc,last_name:asc,full_name:desc
     * @queryParam year string Specifying year filter for when user was created. Example: 2023
     * @queryParam period string Specifying a period to filter users creation date by. Example: 24h
     * @queryParam month string Specifying a month to filter users creation date by. Example: 1
     * @queryParam status string Specifying user status. Example: restricted
     * @queryParam status string Specifying user account verification status. Example: verified
     *
     * @param UserListingQueryParamsRequest $request
     * @return JsonResponse
     */
    public function index(UserListingQueryParamsRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $users = (new CacheDataManager(
                $this->userService,
                'getPaginatedList',
                [$request]
            ))->getData();

            return $this->success('Users List', 200, [
                'users' => $users,
                'options' => ClientOptions::only('users', [
                    'roles',
                    'deleted',
                    'order_by',
                    'order_direction',
                    'reg_years',
                    'months',
                    'time_periods',
                    'status',
                    'action',
                    'verification'
                ]),
                'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Users))
                    ->getDefaultQueryParams()
            ]);
        } catch (NotFoundExceptionInterface $e) {
            Log::error($e);

            return $this->error('Unable to apply filter(s)', 400);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e);

            return $this->error('An error occurred while fetching users', 400);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while fetching users', 400);
        }
    }

    /**
     * Retrieve User Options
     *
     * Fetch available form options
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(): \Illuminate\Http\JsonResponse
    {
        try {
            return $this->success('User Options.', 200, [
                'options' => ClientOptions::only('users', [
                    'roles',
                    'genders',
                    'reg_years',
                    'deleted',
                    'order_by',
                    'order_direction',
                    'action'
                ])
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while fetching user options.', 400);
        }
    }

    /**
     * Retrieve User
     *
     * Get specific user by their ref attribute.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam ref string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param string $ref
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $ref): \Illuminate\Http\JsonResponse
    {
        try {
            $user = (new CacheDataManager(
                $this->userService,
                'show',
                [$ref]
            ))->getData();

            return $this->success('User fetched.', 200, [
                'user' => $user,
                'options' => ClientOptions::only('users', [
                    'roles',
                    'genders',
                    'reg_years',
                    'deleted',
                    'order_by',
                    'order_direction',
                    'action'
                ])
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);

            return $this->error("Oops...We couldn't find the user you were looking for.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while fetching user.', 400);
        }
    }

    /**
     * Retrieve User by email address
     *
     * Get specific user by their email attribute.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam user_email string required Specifies user's email attribute. Example: Mark@runforcharity.com
     *
     * @param string $email
     * @return \Illuminate\Http\JsonResponse
     */
    public function _show(string $email): \Illuminate\Http\JsonResponse
    {
        try {
            $user = (new CacheDataManager(
                $this->userService,
                '_show',
                [$email]
            ))->getData();

            return $this->success('User fetched.', 200, [
                'user' => $user
            ]);

        } catch (ModelNotFoundException $e) {
            Log::error($e);

            return $this->error('The user was not found!', 404);
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error('An error occurred while fetching user.', 400);
        }
    }

    /**
     * Edit User
     *
     * Show specific user's account information for editing.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam ref string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param string $ref
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(string $ref): \Illuminate\Http\JsonResponse
    {
        try {
            $user = (new CacheDataManager(
                $this->userService,
                'edit',
                [$ref]
            ))->getData();

            return $this->success('User info fetched.', 200, [
                'user' => $user,
                'options' => ClientOptions::only('users', [
                    'roles',
                    'genders',
                    'action'
                ])
            ]);
        } catch (ModelNotFoundException $exception) {
            return $this->error("Oops...We couldn't find the user you were looking for.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while fetching user.', 400);
        }
    }

    /**
     * Retrieve Auth/Request User
     *
     * Get currently authenticated user.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function current(): \Illuminate\Http\JsonResponse
    {
        try {
            return $this->success('Current User fetched.', 200, [
                'user' => (new CacheDataManager(
                    $this->userService,
                    'getCurrentUser',
                    [],
                    false,
                    true
                ))->getData()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while fetching current user info.', 400);
        }
    }

    /**
     * Create User
     *
     * Create a user account.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam first_name string required The first name of the user. Example: Wendy
     * @bodyParam last_name string required The last name of the user. Example: Mike
     * @bodyParam email string required The email of the User. Example: user@email.com
     * @bodyParam phone string required The phone number of the User. Example: +12333333333
     * @bodyParam gender string required The gender of the User. Example: female
     * @bodyParam dob date required The password of the User. Example: 2000-12-31
     * @bodyParam roles string[] required The role to be assigned to specified User. Example: ["administrator", "developer"]
     * @bodyParam avatar file The image.
     *
     * @param StoreUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreUserRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $userData = $this->filterDataByKeys($data, ['first_name', 'last_name', 'email', 'phone']);
        $rolesData = $this->filterDataByKeys($data, ['roles']);
        $profileData = $this->filterDataByKeys($data, ['gender', 'dob']);

        try {
            $user = DB::transaction(function () use ($request, $userData, $rolesData, $profileData) {
                if (! is_null($existingUser = User::where('email', $userData['email'])->first())) {
                    $existingUser->sites()->syncWithoutDetaching([clientSiteId()]);
                    $this->existingUserLinkedToSite = true;
                    $user = $existingUser;
                } else {
                    $user = User::create(array_merge($userData, [
//                    'password' => $password = Hash::make(Str::random(10)),
//                    'email_verified_at' => now()
                    ]));

                    $this->assignRolesToUser($rolesData['roles'], $user);
                    $profile = $this->createOrUpdateUserProfile($user, $profileData);
                    $this->attachSingleUploadToModel($profile, $request->avatar, UploadUseAsEnum::Avatar, true);
                }

                $user = $user->fresh();

                if (! $this->existingUserLinkedToSite) {
                    $user->sendPasswordSetupNotification();
                }

                return $user;
            }, 5);

            return $this->success('The user was successfully created!', 201, [
                'user' => $user
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error(
                $this->existingUserLinkedToSite
                    ? 'The user has been linked to the platform!'
                    : 'An error occurred while trying to create user.', 400);
        }
    }

    /**
     * Update User
     *
     * Update edited user account information.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam first_name string required The first name of the user. Example: Wendy
     * @bodyParam last_name string required The last name of the user. Example: Mike
     * @bodyParam email string required The email of the User. Example: user@email.com
     * @bodyParam phone string required The phone number of the User. Example: +12333333333
     * @bodyParam gender string required The gender of the User. Example: female
     * @bodyParam dob date required The date of birth of the User. Example: 2000-12-31
     * @bodyParam roles string[] required The role to be assigned to specified User. Example: ["administrator", "developer"]
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateUserRequest $request, User $user): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $userData = $this->filterDataByKeys($data, ['first_name', 'last_name', 'email', 'phone']);
        $rolesData = $this->filterDataByKeys($data, ['roles']);
        $profileData = $this->filterDataByKeys($data, ['gender', 'dob']);

        try {
            $user = DB::transaction(function () use ($request, $user, $userData, $rolesData, $profileData) {
                $user->update($userData);
                $this->assignRolesToUser($rolesData['roles'], $user);
                $this->createOrUpdateUserProfile($user, $profileData);

                return $user;
            }, 5);

            return $this->success('User has been Updated.', 201, [
                'user' => $user->load(['activeRole', 'roles', 'profile'])
            ]);
        } catch (ModelNotFoundException $exception) {
            return $this->error("Oops...We couldn't find the user you were trying to update.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to update user', 400);
        }
    }

    /**
     * Delete Many Users
     *
     * Delete multiple users' data by specifying their ids.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam users_ids string[] required The list of ids associated with users. Example: [1,2]
     * @queryParam force string Optionally specifying to force-delete model, instead of the default soft-delete. Example: 1
     *
     * @param DeleteUsersRequest $request
     * @return JsonResponse
     */
    public function destroyMany(DeleteUsersRequest $request): JsonResponse
    {
        try {
            $force = (request('force') == 1);
            $response = (new SoftDeleteableManagementService(User::class))
                ->delete($request->validated('users_ids'), 'force');

            return $this->success('User(s) has been '. ($force ? 'permanently ' : null) . 'deleted.', 200, [
                'users' => (new CacheDataManager(
                    $this->userService,
                    'getPaginatedList',
                    [$request],
                    true
                ))->getData(),
            ]);
        } catch (DeletionConfirmationRequiredException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode(), $exception->payload);
        } catch (InvalidSignatureForHardDeletionException $exception) {
            Log::error($exception);

            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while deleting specified user(s).', 400);
        }
    }

    /**
     * Restore Many Users
     *
     * Restore multiple users data by specifying their ids.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam users_ids string[] required The list of ids associated with users. Example: [1,2]
     *
     * @param RestoreUsersRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function restoreMany(RestoreUsersRequest $request): JsonResponse
    {
        try {
            $response = (new SoftDeleteableManagementService(User::class))
                ->restore($request->validated('users_ids'));

            return $this->success('Specified user(s) has been restored.', 200, [
                'users' => (new CacheDataManager(
                    $this->userService,
                    'getPaginatedList',
                    [$request],
                    true
                ))->getData()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while restoring specified user(s).', 400);
        }
    }

    /**
     * Add Many Users to Site
     *
     * Add multiple users to current site by specifying their ids.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam users_ids string[] required The list of ids associated with users. Example: [1,2]
     *
     * @param AddUsersToSiteRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function addToSite(AddUsersToSiteRequest $request): JsonResponse
    {
        try {
            Site::whereId(clientSiteId())
                ->firstOrFail()
                ->users()
                ->attach($request->validated('users_ids'));

            return $this->success('Specified user(s) successfully added to site.', 200, [
                'users' => (new CacheDataManager(
                    $this->userService,
                    'getPaginatedList',
                    [$request],
                    true
                ))->getData()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while adding specified user(s) to site.', 400);
        }
    }

    /**
     * Remove User from Site
     *
     * Multi-remove user(s) from site.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam users_ids string[] required The list of ids associated with specific users. Example: [1,2]
     * @queryParam force string Optional parameter to indicate that user record should be deleted permanently. Example: 1
     *
     * @param RemoveUsersFromSiteRequest $request
     * @return JsonResponse
     */
    public function removeFromSite(RemoveUsersFromSiteRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            Site::whereId(clientSiteId())
                ->firstOrFail()
                ->users()
                ->detach($request->validated('users_ids'));

            return $this->success('User Account(s) removed from this site.', 200, [
                'users' => (new CacheDataManager(
                    $this->userService,
                    'getPaginatedList',
                    [$request],
                    true
                ))->getData()
            ]);
        } catch (ModelNotFoundException $exception) {
            return $this->error("Oops...We couldn't find the user you were trying to remove.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while trying to remove user from this site.', 400);
        }
    }

    /**
     * Site Users Action
     *
     * Take an action by specifying their ids and the action.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam users_ids string[] required The list of ids associated with users. Example: [1,2]
     * @bodyParam action string required The reason for restricting the user. Example: restrict
     *
     * @param UsersActionRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function takeAction(UsersActionRequest $request): JsonResponse
    {
        switch ($request->validated('action')) {
            case SiteUserActionEnum::Restrict->value:
                $status = SiteUserStatus::Restricted->value;
                $successTag = 'placed on';
                $errorTag = 'restricting';
                break;
            case SiteUserActionEnum::Unrestrict->value:
                $status = SiteUserStatus::Active->value;
                $successTag = 'removed from';
                $errorTag = 'removing restriction on';
                break;
            default:
                return $this->error('Invalid action specified.', 400);
        }

        try {
            SiteUser::query()
                ->whereIn('user_id', $ids = $request->validated('users_ids'))
                ->where('site_id', clientSiteId())
                ->update(['status' => $status]);

            CacheDataManager::flushAllCachedServiceListings(new UserDataService);

            return $this->success("Restriction has been $successTag specified user account(s).", 200, [
                'users' => $this->userService->findMany($ids)
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error("An error occurred while $errorTag specified user account(s).", 400);
        }
    }

    /**
     * Export users
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam role string Specifying the user role to query by. Example: administrator
     * @queryParam term string Specifying a keyword similar to first_name, last_name, phone, or email. Example: john@email
     * @queryParam per_page string Overriding the default (10) number of listings per-page. Example: 20
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: first_name:desc,last_name:asc,full_name:desc
     * @queryParam year string Specifying year filter for when user was created. Example: 2023
     * @queryParam period string Specifying a period to filter users creation date by. Example: 24h
     * @queryParam month string Specifying a month to filter users creation date by. Example: 1
     * @queryParam status string Specifying user status. Example: active
     *
     * @param UserListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(UserListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->userService->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while exporting users\' data.', 400);
        }
    }

    /**
     * User Settings
     *
     * Get settings information for a user.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param string $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function settings(string $user): JsonResponse
    {
        try {
            $settings = (new CacheDataManager(
                $this->userService,
                'getSettings',
                [$user]
            ))->getData();

            return $this->success('User Settings', 200, [
                'settings' => $settings,
                'options' => ClientOptions::all('users'),
            ]);
        } catch (ModelNotFoundException $exception) {
            return $this->error("Oops...We couldn't find the user you were looking for.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching user info.', 400);
        }
    }

    /**
     * @param array $roles
     * @param User $user
     * @return void
     * @throws \Exception
     */
    private function assignRolesToUser(array $roles, User $user): void
    {
        $ids = [];

        foreach ($roles as $role) {
            $match = Role::firstWhere('name', $role);
            if (isset($match->id)) $ids[] = $match->id;
        }

        $user->syncRolesOnCurrentSite($ids);
        $user->assignDefaultActiveRole();

        if ($user->isParticipant()) {
            foreach (User::RoleDefaultPermissions[RoleNameEnum::Participant->name] as $permission) {
                $user->grant($permission);
            }
        }
    }

    /**
     * @param User $user
     * @param mixed $data
     * @return \Illuminate\Database\Eloquent\Model|CanHaveUploadableResource
     */
    private function createOrUpdateUserProfile(User $user, mixed $data): \Illuminate\Database\Eloquent\Model|CanHaveUploadableResource
    {
        return $user->profile()->updateOrCreate([
            'gender' => GenderEnum::tryFrom($data['gender'])?->value,
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
