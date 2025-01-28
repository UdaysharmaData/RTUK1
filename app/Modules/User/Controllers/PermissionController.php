<?php

namespace App\Modules\User\Controllers;

use App\Modules\User\Models\Permission;
use App\Traits\Response;
use Illuminate\Http\Request;

class PermissionController
{
    use Response;

    /**
     * Get Permissions
     *
     * List system permissions.
     *
     * @group Permission
     * @authenticated
     * @header Content-Type application/json
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        return $this->success('All assignable permissions.', 200, [
            'permissions' => Permission::all()
        ]);
    }

    /**
     * New permission
     *
     * Add a new permission to the system.
     *
     * @group Permission
     * @authenticated
     * @header Content-Type application/json
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(Permission::RULES['create_or_update']);

        try {
            $role = Permission::create($validated);

            return $this->success('New permission has been created!', 201, [
                'permission' => $role
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to create a new permission.', 400);
        }
    }

    /**
     * Update Permission
     *
     * Update an existing permission.
     *
     * @group Permission
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam name string required Permission's name. Example: suspend-users
     * @bodyParam description string Provides optional description. Example: User with this permission can suspend other users
     * @urlParam user string required Specifies permission's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param Request $request
     * @param Permission $permission
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Permission $permission): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate(Permission::RULES['create_or_update']);

        try {
            $permission->update($validated);

            return $this->success('Permission has been updated!', 201, [
                'permission' => $permission->refresh()
            ]);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update permission.', 400);
        }
    }

    /**
     * Delete Permission
     *
     * Delete an existing permission.
     *
     * @group Permission
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam user string required Specifies permission's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param Request $request
     * @param Permission $permission
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, Permission $permission): \Illuminate\Http\JsonResponse
    {
        try {
            $permission->delete();

            return $this->success('Permission deleted!');
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete permission.', 400);
        }
    }
}
