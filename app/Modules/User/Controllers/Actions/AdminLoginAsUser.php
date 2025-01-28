<?php

namespace App\Modules\User\Controllers\Actions;

use App\Enums\RoleNameEnum;
use App\Http\Controllers\Controller;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminLoginAsUser extends Controller
{
    use Response;

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
    public function __invoke(User $user): \Illuminate\Http\JsonResponse
    {
        try {
            $current = Auth::guard()->user();
            $current->token()->revoke();
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
}
