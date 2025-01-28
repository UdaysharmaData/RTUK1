<?php

namespace App\Modules\User\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\Permission;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\GrantPermissionRequest;
use App\Traits\Response;

class GrantPermission extends Controller
{
    use Response;

    /**
     * Grant Permission
     *
     * Admin grant permission to user.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam permission_name string required Specifies permission's name attribute. Example: suspend-users
     * @urlParam user string required Specifies user's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param GrantPermissionRequest $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(GrantPermissionRequest $request, User $user): \Illuminate\Http\JsonResponse
    {
        try {
            $permission = Permission::whereName($request->validated('permission_name'))->firstOrFail();
            $user->grant($permission);

            return $this->success('Permission has been granted!', 201, [
                'user' => $user->refresh()
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to grant permission.', 400);
        }
    }
}
