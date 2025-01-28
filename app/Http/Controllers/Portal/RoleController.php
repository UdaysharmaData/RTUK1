<?php

namespace App\Http\Controllers\Portal;

use Str;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Modules\User\Models\Role;
use App\Http\Requests\RoleRequest;

/**
 * @group Roles
 * Manage roles on the application
 * @authenticated
 */
class RoleController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Role Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the roles on the application.
    |
    */

    use Response;

    public function __construct()
    {
        parent::__construct();

        // $this->middleware('role:can_manage_roles');
    }

    /**
     * A listing of roles.
     *
     * @return JsonResponse
     */
    public function roles(): JsonResponse
    {
        return $this->success('The list of roles', 201, Role::all());
    }

    /**
     * Create a role
     *
     * @param RoleRequest $request
     * @return JsonResponse
     */
    public function create(RoleRequest $request): JsonResponse
    {
        try {
            $role = new Role();
            $role->name = $request->name;
            $role->display_name = $request->display_name;
            $role->description = $request->description;
            $role->permissions = implode(',', $request->permissions);
            $role->save();
        } catch (QueryException $e) {
            return $this->error('Unable to create the role! Please try again', 406);
        }

        return $this->success('Successfully created the role!', 200, $role);
    }

    /**
     * Get a role details
     * 
     * @urlParam name string required The role name (slug). Example: account_managers
     * @return JsonResponse
     */
    public function role(string $name): JsonResponse
    {
        try {
            $role = Role::where('name', $name)->firstOrFail();
        } catch(ModelNotFoundException $e) {
            return $this->error('The role was not found!', 404);
        }

        return $this->success('The role details', 200, $role);
    }

    /**
     * Update a role
     * 
     * @param RoleRequest $request
     * @urlParam _name string required The role name (slug). Example: account_managers
     * @return JsonResponse
     */
    public function update(RoleRequest $request, string $_name): JsonResponse
    {
        try {
            $role = Role::where('name', $_name)->firstOrFail();

            try {
                $role->fill($request->all());
                $role->display_name = $request->display_name;
                $role->permissions = implode(',', $request->permissions);
                $role->save();
            } catch(QueryException $e) {
                return $this->error('Unable to update the role! Please try again.', 406);
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The role was not found!', 404);
        }

        return $this->success('Successfully updated the role!', 200, $role);
    }

    /**
     * Delete a role
     * 
     * @urlParam name string required. The role name (slug). Example: account_managers
     * @return JsonResponse
     */
    public function delete(string $name): JsonResponse
    {
        try {
            $role = Role::where('name', $name)->firstOrFail();
            try {
                $role->delete();
            } catch (QueryException $e) {
                return $this->error('Unable to delete the role! Please try again.', 406);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The role was not found!', 404);
        }

        return $this->success('Successfully deleted the role!', 200, $role);
    }
}
