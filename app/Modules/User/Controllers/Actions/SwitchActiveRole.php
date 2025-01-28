<?php

namespace App\Modules\User\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\ActiveRole;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\UpdateActiveRoleRequest;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;
use App\Traits\Response;
use Illuminate\Support\Facades\Log;

class SwitchActiveRole extends Controller
{
    use Response;

    /**
     * Set/Switch Active Role
     *
     * Admin can switch between their assigned roles.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam role string required Specifies the name attribute of the new role to be assigned as active role. Example: participant
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param UpdateActiveRoleRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(UpdateActiveRoleRequest $request, User $user): \Illuminate\Http\JsonResponse
    {
        try {
            $roleId = Role::whereName($request->validated('role'))->firstOrFail()?->id;

            ActiveRole::updateOrCreate(
                ['user_id' => $user->id, 'site_id' => clientSiteId()],
                ['role_id' => $roleId],
            );

            CacheDataManager::flushAllCachedServiceListings(new UserDataService);

            return $this->success('Role Switched.', 200, [
                'user' => $user->load(['activeRole', 'profile'])
            ]);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->error('An error occurred while trying to switch between roles.', 400);
        }
    }
}
