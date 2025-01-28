<?php

namespace App\Http\Controllers\Portal;

use App\Traits\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Modules\User\Models\Permission;
use App\Http\Requests\PermissionRequest;

/**
 * @group Permissions
 * Manage permissions on the application
 * @authenticated
 */
class PermissionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Permission Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the permissions on the application.
    |
    */

    use Response;

    public function __construct()
    {
        parent::__construct();

        // $this->middleware('role:can_manage_permissions');
    }

    /**
     * A listing of permissions.
     *
     * @queryParam page integer The page data to return. Example: 1
     * @param Request
     * @return JsonResponse
     */
    public function permissions(Request $request): JsonResponse
    {
        return $this->success('The list of permissions', 201, Permission::paginate(10));
    }

    /**
     * Create a permission
     *
     * @param PermissionRequest $request
     * @return JsonResponse
     */
    public function create(PermissionRequest $request): JsonResponse
    {
        try {
            $permission = new Permission();
            $permission->name = $request->name;
            $permission->display_name = $request->display_name;
            $permission->description = $request->description;
            $permission->save();
        } catch (QueryException $e) {
            return $this->error('Unable to create the permission! Please try again', 406);
        }

        return $this->success('Successfully created the permission!', 200, $permission);
    }

    /**
     * Get a permission details
     * 
     * @urlParam name string required The permission name (slug). Example: can_manage_userss
     * @return JsonResponse
     */
    public function permission(string $name): JsonResponse
    {
        try {
            $permission = Permission::where('name', $name)->firstOrFail();
        } catch(ModelNotFoundException $e) {
            return $this->error('The permission was not found!', 404);
        }

        return $this->success('The permission details', 200, $permission);
    }

    /**
     * Update a permission
     * 
     * @param PermissionRequest $request
     * @urlParam _name string required The permission name (slug). Example: can_manage_userss
     * @return JsonResponse
     */
    public function update(PermissionRequest $request, string $_name): JsonResponse
    {
        try {
            $permission = Permission::where('name', $_name)->firstOrFail();

            try {
                $permission->fill($request->all());
                $permission->display_name = $request->display_name;
                $permission->save();
            } catch(QueryException $e) {
                return $this->error('Unable to update the permission! Please try again.', 406);
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The permission was not found!', 404);
        }

        return $this->success('Successfully updated the permission!', 200, $permission);
    }

    /**
     * Delete a permission
     * 
     * @urlParam name string required. The permission name (slug). Example: can_manage_userss
     * @return JsonResponse
     */
    public function delete(string $name): JsonResponse
    {
        try {
            $permission = Permission::where('name', $name)->firstOrFail();
            try {
                $permission->delete();
                // TODO: Loop through the roles and remove the id of the deleted permission.
                // This is not necessary given that after revamping the database, we shall have a role_permission schema which will
                // delete the record once the permission is deleted (using cascade)
            } catch (QueryException $e) {
                return $this->error('Unable to delete the permission! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The permission was not found!', 404);
        }

        return $this->success('Successfully deleted the permission!', 200, $permission);
    }
}
