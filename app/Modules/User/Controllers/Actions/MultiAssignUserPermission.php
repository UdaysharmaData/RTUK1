<?php

namespace App\Modules\User\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\MultiAssignUserPermissionRequest;
use App\Traits\Response;

class MultiAssignUserPermission extends Controller
{
    use Response;

    /**
     * Multi Assign Permissions
     *
     * Admin can grant multiple users multiple permissions.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam permissions string[] required This is an array specifying the ids of permissions to be assigned. Example: [1, 2]
     * @bodyParam users string[] required This is an array specifying the ids of users to be assigned. Example: [4, 2, 3]
     *
     * @param MultiAssignUserPermissionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(MultiAssignUserPermissionRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $users = User::query()->findOrFail($request->validated('users'));
//            $permissions = Permission::query()->findOrFail($request->validated('permissions'));

            $users->each(
                fn ($user) => $user->permissions()
                    ->syncWithoutDetaching($request->validated('permissions'))
            );

            return $this->success('Permission(s) have been granted for user(s).', 201, [
                $users => $users->refresh()
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to grant permission(s) to users(s).', 400);
        }
    }
}
