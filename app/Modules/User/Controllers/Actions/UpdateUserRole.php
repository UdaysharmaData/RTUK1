<?php

namespace App\Modules\User\Controllers\Actions;

use App\Traits\Response;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\UserDataService;
use App\Modules\User\Requests\UpdateUserRoleRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateUserRole extends Controller
{
    use Response;

    /**
     * Update Role
     *
     * Update Role assigned to User.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     * @bodyParam roles string[] required The role to be assigned to specified User. Example: ["administrator", "developer"]
     *
     * @param UpdateUserRoleRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(UpdateUserRoleRequest $request, User $user): \Illuminate\Http\JsonResponse
    {
        try {
            $data = $request->validated();
            $ids = [];
            foreach ($data['roles'] as $role) {
                $role = Role::firstWhere('name', $role);
                if (isset($role->id)) $ids[] = $role->id;
            }

            DB::transaction(function () use ($ids, $user) {
                $user->syncRolesOnCurrentSite($ids);
                $user->assignDefaultActiveRole();

                CacheDataManager::flushAllCachedServiceListings(new UserDataService);
            });

            return $this->success('User Role Updated.', 201, [
                'user' => $user->load(['activeRole', 'roles', 'profile'])
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception->getMessage());
            return $this->error("Oops...We couldn't find this user you were trying to update.", 400);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update user role', 400);
        }
    }
}
