<?php

namespace App\Modules\User\Controllers;

use App\Enums\ListTypeEnum;
use App\Facades\ClientOptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoleListingQueryParamsRequest;
use App\Modules\User\Models\Role;
use App\Modules\User\Requests\DeleteRolesRequest;
use App\Modules\User\Requests\RestoreRolesRequest;
use App\Modules\User\Requests\StoreRoleRequest;
use App\Modules\User\Requests\UpdateRoleRequest;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\RoleDataService;
use App\Services\DefaultQueryParamService;
use App\Services\SoftDeleteable\Exceptions\DeletionConfirmationRequiredException;
use App\Services\SoftDeleteable\Exceptions\InvalidSignatureForHardDeletionException;
use App\Services\SoftDeleteable\SoftDeleteableManagementService;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class RoleController extends Controller
{
    use Response;

    public function __construct(protected RoleDataService $roleService)
    {
        parent::__construct();
    }

    /**
     * Roles' Listing
     *
     * Get paginated application roles' list.
     *
     * @group Role
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam keyword string Specifying the search query. Example: admin
     * @queryParam role string Specifying the user role to query by. Example: administrator
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:asc
     *
     * @param RoleListingQueryParamsRequest $request
     * @return JsonResponse
     */
    public function index(RoleListingQueryParamsRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $roles = (new CacheDataManager(
                $this->roleService,
                'getPaginatedList',
                [$request]
            ))->getData();

            return $this->success('Roles List', 200, [
                'roles' => $roles,
                'options' => ClientOptions::only('roles', [
                    'deleted',
                    'order_by',
                    'order_direction'
                ]),
                'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Roles))->getDefaultQueryParams()
            ]);
        } catch (NotFoundExceptionInterface $e) {
            Log::error($e);

            return $this->error('Unable to apply filter(s)', 400);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e);

            return $this->error('An error occurred while fetching roles', 400);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while fetching roles', 400);
        }
    }

    /**
     * Retrieve Role Options
     *
     * Fetch available form options
     *
     * @group Role
     * @authenticated
     * @header Content-Type application/json
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(): \Illuminate\Http\JsonResponse
    {
        try {
            return $this->success('Role Options.', 200, [
                'options' => ClientOptions::only('roles', ['deleted', 'order_by', 'order_direction'])
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while fetching role options.', 400);
        }
    }

    /**
     * Retrieve Role
     *
     * Get specific role by their ref attribute.
     *
     * @group Role
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam ref string required Specifies role's ref attribute. Example: 975dcf12-eda2-4437-8c96-6df4e790d074
     *
     * @param string $ref
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $ref): \Illuminate\Http\JsonResponse
    {
        try {
            $role = Role::withTrashed()
                ->where('ref', '=', $ref)
                ->firstOrFail();

            return $this->success('Role fetched.', 200, [
                'role' => $role
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error("Oops...We couldn't find the role you were looking for.", 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching role.', 400);
        }
    }

    /**
     * Create Role
     *
     * Create a new role.
     *
     * @group Role
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam name string required The name of the role. Example: admin
     * @bodyParam description string The description of the role. Example: user with admin privileges
     *
     * @param StoreRoleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreRoleRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $role = Role::create($request->validated());

            return $this->success('Successfully created the role!', 201, [
                'role' => $role
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while trying to create a new role.', 400);
        }
    }

    /**
     * Update Role
     *
     * Update an existing role.
     *
     * @group Role
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam name string The name of the role. Example: admin
     * @bodyParam description string The description of the role. Example: user with admin privileges
     *
     * @param UpdateRoleRequest $request
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRoleRequest $request, Role $role): \Illuminate\Http\JsonResponse
    {
        try {
            $role->update($request->validated());

            return $this->success('Successfully updated the role!', 200, [
                'role' => $role
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error("An error occurred while trying to update role", 400);
        }
    }

    /**
     * Delete Many Roles
     *
     * Delete multiple roles' data by specifying their ids.
     *
     * @group Role
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam roles_ids string[] required The list of ids associated with roles. Example: [1,2]
     * @queryParam force string Optionally specifying to permanently delete model, instead of the default soft-delete. Example: 1
     *
     * @param DeleteRolesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteRolesRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $force = (request('force') == 1);
            $response = (new SoftDeleteableManagementService(Role::class))
                ->delete($request->validated('roles_ids'), 'force');

            return $this->success('Role(s) has been '. ($force ? 'permanently ' : null) . 'deleted.', 200, [
                'roles' => (new CacheDataManager(
                    $this->roleService,
                    'getPaginatedList',
                    [$request],
                    true
                ))->getData(),
            ]);
        } catch (DeletionConfirmationRequiredException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode(), $exception->payload);
        } catch (InvalidSignatureForHardDeletionException $exception) {
            Log::error($exception);

            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while deleting specified role(s).', 400);
        }
    }

    /**
     * Restore Many Roles
     *
     * Restore multiple roles data by specifying their ids.
     *
     * @group Role
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam roles_ids string[] required The list of ids associated with roles. Example: [1,2]
     *
     * @param RestoreRolesRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function restore(RestoreRolesRequest $request): JsonResponse
    {
        try {
            $response = (new SoftDeleteableManagementService(Role::class))
                ->restore($request->validated('roles_ids'));

            return $this->success('Specified role(s) has been restored.', 200, [
                'roles' => (new CacheDataManager(
                    $this->roleService,
                    'getPaginatedList',
                    [$request],
                    true
                ))->getData()
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while restoring specified role(s).', 400);
        }
    }
}
