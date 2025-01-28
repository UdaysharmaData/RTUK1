<?php

namespace App\Modules\User\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\Permission;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\RevokePermissionRequest;
use App\Traits\Response;

class RevokePermission extends Controller
{
    use Response;

    /**
     * Revoke Permission
     *
     * Admin can revoke a user permission.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param RevokePermissionRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(RevokePermissionRequest $request, User $user): \Illuminate\Http\JsonResponse
    {
        try {
            $permission = Permission::whereName($request->validated('permission_name'))->firstOrFail();
            $user->revoke($permission);

            return $this->success('Permission has been revoked!', 201, [
                'user' => $user->refresh()
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to revoke permission.', 400);
        }
    }
}
