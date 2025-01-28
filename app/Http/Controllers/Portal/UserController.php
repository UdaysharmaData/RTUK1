<?php

namespace App\Http\Controllers\Portal;

use Str;
use Auth;
use Validator;
use App\Mail\Mail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\AccountType;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\UserCreateRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Traits\Response;
use App\Traits\SiteTrait;

use App\Enums\RoleNameEnum;
use App\Enums\RoleMailEnum;
use App\Jobs\ResendEmailJob;
use App\Mail\UserAccountCreatedMail;

use App\Models\User;
use App\Modules\User\Models\Role;
use App\Models\Site;
use App\Models\Charity;
use App\Models\CharityData;
use Illuminate\Support\Facades\Log;

/**
 * @group Users
 * Manages users on the application
 * @authenticated
 */
class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | User Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with users. That is
    | the creation, view, update, delete and more ...
    |
    */

    use Response, SiteTrait;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // $this->middleware('role:can_manage_users', [
        //     'except' => [
        //         'profile',
        //         'change',
        //         'updateProfile',
        //         'removeGallery',
        //         'payInvoice'
        //     ]
        // ]);
    }

    /**
     * The list of users
     *
     * @queryParam role string Filter by role. No-example
     * @queryParam term string Filter by term. The term to search for. It can be the user name or email No-example
     * @queryParam page integer The page data to return Example: 1
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function users(Request $request): JsonResponse
    {
        $users = User::with('role');

        if (AccountType::isVirtualAdmin()) {
            $users = $users->whereHas('role', function($query) {
                $query->where('name', 'virtual_participant');
            });
        }

        if (AccountType::isRankingsAdmin()) {
            $users = $users->whereHas('role', function($query) {
                $query->where('name', 'participant');
            })->has('raceResults');
        }

        if ($request->filled('role')) {
            $users = $users->whereHas('role', function($query) use ($request) {
                $query->where('name', $request->role);
            });
        }

        if ($request->filled('term')) {
            $users = $users->where(function($query) use ($request) {
                $query->where('email', 'LIKE', '%'.$request->term.'%');
                $query->orWhere('first_name', 'LIKE', '%'.$request->term.'%');
                $query->orWhere('last_name', 'LIKE', '%'.$request->term.'%');
            });
        }

        $users = $users->paginate(10);

        return $this->success('The list of users', 200, $users);
    }

    /**
     * Create a new user
     *
     * Generate a password and send via email
     *
     * @param UserCreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(UserCreateRequest $request): JsonResponse
    {
        if (AccountType::isVirtualAdmin() || AccountType::isRankingsAdmin()) {
            return $this->error('UnAuthorized', 403, 'No authorization to access this resource!');
        }

        try {
            $user = new User();
            $user->role_id = Role::where('name', $request->role)->first()->id;
            $user->default_site_id = Site::where('domain', $request->default_site)->first()->id;

            if (AccountType::isManager() || AccountType::isCharity()) { // account managers and charity owners can only create charity users for the charities they manage or own respectively
                $user->role_id = Role::where('name', RoleNameEnum::CharityUser)->first()->id;
                $user->charity_id = $request->charity_id;
            } else {
                if ($request->role == RoleNameEnum::CharityUser->value) {
                   $user->charity_id = $request->charity_id;
                }
            }

            // Generate a password
            $password = Str::random(8);
            $user->password = \Hash::make($password);
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name ?? '';
            $user->company = $request->company ?? '';
            $user->temp_pass = 1; // Prompts the user to update it's password upon authentication

            // Trim the email address
            $user->email = trim($request->email);

            $user->save();

            // Notify the user via email
            $role = $user->role->name;
            $reflection = new \ReflectionEnum(RoleMailEnum::class);
            if ($reflection->hasConstant($role)) { // Get the email to be sent from the user role.
                $mail = $reflection->getConstant($role)->value;

                try {
                    Mail::site()->to($user->email)->send(new $mail($user, $password));
                } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
                    Log::channel(static::getSite()?->code . 'mailexception')->info("Create a new user");
                    Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                    dispatch(new ResendEmailJob(new $mail($user, $password), clientSite()));
                } catch (\Exception $e) {
                    Log::channel(static::getSite()?->code . 'mailexception')->info("Create a new user");
                    Log::channel(static::getSite()?->code . 'mailexception')->info($e);
                    dispatch(new ResendEmailJob(new $mail($user, $password), clientSite()));
                }
            }
        } catch (QueryException $e) {
            return $this->error('Unable to create the user! Please try again', 406);
        }

        return $this->success('Successfully created the user!', 201, $user);
    }

    /**
     * Get the user profile
     *
     * @urlParam email string required The user email. Example: marc@runforcharity.com
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(string $email): JsonResponse
    {
        try {
            $user = User::where('email', $email)->firstOrFail();

            switch ($user->role->name) {
                case 'charity':
                    $user['charity_data'] = CharityData::where('charity_id', $user->charity->id)
                        ->whereHas('site', function ($query) {
                            $query->where('id', static::getSite()?->id);
                        })
                        ->first();
                    $user['percentage'] = Charity::percentComplete($user->charity);
                    break;
                case 'charity_user':
                    $user['charity_data'] = CharityData::where('charity_id', $user->charity->id)
                        ->whereHas('site', function ($query) {
                            $query->where('id', static::getSite()?->id);
                        })
                        ->first();
                    $user['percentage'] = Charity::percentComplete($user->charity);
                    break;
                default:
                    break;
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The user was not found', 406);
        }

        return $this->success('User profile', 200, $user);
    }

    /**
     * Update the user information
     *
     * @urlParam id integer required The user id. Example: 7
     * @param  UserUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UserUpdateRequest $request, int $id): jsonResponse
    {
        try {
            $user = User::where('id', $id);

            if ($id != Auth::user()->id) { // when updating another user data
                if (AccountType::isVirtualAdmin()) {
                    $user = $user->whereHas('role', function($query) {
                        $query->where('name', RoleNameEnum::VirtualParticipant);
                    });
                }

                if (AccountType::isRankingsAdmin()) {
                    $user = $user->whereHas('role', function($query) {
                        $query->where('name', RoleNameEnum::Participant);
                    })->has('raceResults');
                }

                if (AccountType::isManager()) {
                    $user = $user->whereHas('charity', function($query) {
                        $query->where('manager_id', Auth::user()->id);
                    });
                }

                if (AccountType::isCharity()) {
                    $user = $user->whereHas('charity', function($query) {
                        $query->where('user_id', Auth::user()->id);
                    });
                }

                if (!AccountType::isAdmin()) {
                    return $this->error('UnAuthorized', 403, 'No authorization to access this resource!');
                }
            }

            $user = $user->firstOrfail();

            try {
                if ($id == Auth::user()->id) { // only the user can update it's password
                    if (isset($request->password) && $request->password) {
                        $request['password'] = \Hash::make($request->password);
                    } else {
                        unset($request['password']);
                    }
                }

                $request['default_site_id'] = Site::where('domain', $request->default_site)->first()->id;

                if (AccountType::isAdmin()) { // only admins can update a user's role & charity_id
                    $request['role_id'] = Role::where('name', $request->role)->first()->id;
                    if ($request->role == RoleNameEnum::CharityUser->value) {
                        $request['charity_id'] = $request->charity_id;
                    }
                } else {
                    unset($request['role_id']);
                    unset($request['charity_id']);
                }

                $user->update($request->all());
            } catch (QueryException $e) {
                return $this->error('Unable to update the user! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The user was not found!', 404);
        }

        return $this->success('Succesfully updated the user!', 200, $user);
    }

    /**
     * Delete a user
     *
     * @urlParam email string required The user email Example: worthto@yahoo.co.uk
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(string $email): JsonResponse
    {
        try {
            $user = User::where('email', $email);

            if (AccountType::isVirtualAdmin()) {
                $user = $user->whereHas('role', function($query) {
                    $query->where('name', 'virtual_participant');
                });
            }

            if (AccountType::isRankingsAdmin()) {
                $user = $user->whereHas('role', function($query) {
                    $query->where('name', 'participant');
                });
            }

            $user = $user->firstOrFail();

            try {
                $user->delete();
            } catch (QueryException $e) {
                return $this->error('Unable to delete the user! Please try again', 406);
            }

        } catch (ModelNotFoundException $e) {
            return $this->error('The user was not found!', 404);
        }

        return $this->success('Successfully deleted the user!', 200, $user);
    }

    /**
     * Change user (authenticated) password
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            // New password. Example: Pass*149
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            // Current password. Example: Pass*149
            'current_password' => [
                'required',
                'string'
            ]
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        if (!(\Hash::check($request->current_password, Auth::user()->password))) { // The passwords matches
            return $this->error('Your current password does not matches with the password you provided! Please try again.', 406);
        }

        if (strcmp($request->current_password, $request->password) == 0) { // Current password and new password are same
            return $this->error('New password cannot be the same as your current password! Please choose a different password.', 406);
        }

        // Check Password History
        // $passwordRecords = $user->passwordRecords()->take(config('app.password_history_num'))->get();
        // foreach($passwordRecords as $passwordRecord){
        //     if (\Hash::check($request->password, $passwordRecord->password)) { // The passwords matches
        //         return $this->error('Your new password can not be same as any of your recent passwords. Please choose a new password', 406);
        //     }
        // }

        try {
            // Change Password
            $user->password = \Hash::make($request->password);
            $user->save();

            // Entry into password history
            // $passwordRecord = App\Models\PasswordRecord::create([
            //     'user_id' => $user->id,
            //     'password' => bcrypt($request->get('new-password')),
            //     'expires_at' => Carbon::now()->addDays(30)
            // ]);
        } catch (QueryException $e) {
            return $this->error('Unable to change the password! Please try again.', 406);
        }

        return $this->success('Successfully changed the password!', 200, $user);
    }

    /**
     * Login as another user
     *
     * @urlParam id integer required The user id. Example: 7
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginAsAnotherUser(int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            try {
                Auth::login($user);
                $user = Auth::user();
            } catch (AuthenticationException $e) {
                return $this->error('Unable to login! Please try again', 406);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The user was not found!', 404);
        }

        return $this->success('Login Successful!', 200, [
            'user' => $user,
            'token' => $user->createToken('Grant Client')->accessToken
        ]);
    }
}
