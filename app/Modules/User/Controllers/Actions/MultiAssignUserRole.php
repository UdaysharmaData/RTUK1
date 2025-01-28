<?php

namespace App\Modules\User\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\MultiAssignUserRoleRequest;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class MultiAssignUserRole extends Controller
{
    use Response;

    /**
     * Multi Assign Roles
     *
     * Admin can assign multiple users to multiple roles.
     *
     * @group User
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam roles string[] required This is an array specifying the ids of roles to be assigned. Example: [1, 2]
     * @bodyParam users string[] required This is an array specifying the ids of users to be assigned. Example: [4, 2, 3]
     *
     * @param MultiAssignUserRoleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(MultiAssignUserRoleRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $users = User::query()->findOrFail($request->validated('users'));

            $users->each(function($user) use($request) {
                $user->roles()->syncWithoutDetaching($request->validated('roles'));
                $user->assignDefaultActiveRole();
            });

            return $this->success('Role(s) have been granted for user(s).', 201, [
                $users => $users->fresh()
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception->getMessage());
            return $this->error("Oops...We couldn't find this user you were trying to update.", 400);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to grant role(s) to users(s).', 400);
        }
    }
}
