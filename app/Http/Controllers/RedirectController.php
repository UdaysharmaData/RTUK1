<?php

namespace App\Http\Controllers;

use App\Enums\ListTypeEnum;
use App\Facades\ClientOptions;
use App\Http\Requests\DeleteRedirectsRequest;
use App\Http\Requests\RedirectListingQueryParamsRequest;
use App\Http\Requests\RestoreRedirectsRequest;
use App\Http\Requests\StoreRedirectRequest;
use App\Http\Requests\UpdateRedirectRequest;
use App\Models\Redirect;
use App\Models\Upload;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\RedirectDataService;
use App\Services\DefaultQueryParamService;
use App\Services\SoftDeleteable\Exceptions\DeletionConfirmationRequiredException;
use App\Services\SoftDeleteable\Exceptions\InvalidSignatureForHardDeletionException;
use App\Services\SoftDeleteable\SoftDeleteableManagementService;
use App\Traits\Response;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class RedirectController extends Controller
{
    use Response;

    public function __construct(protected RedirectDataService $redirectDataService)
    {
        parent::__construct();
    }

    /**
     * Get Redirects
     *
     * Get paginated list of redirects.
     *
     * @group Redirects
     * @authenticated
     * @header Content-Type application/json
     *
     * @queryParam soft_delete string The soft delete status for the redirect. Example: temporal
     * @queryParam hard_delete string The hard delete status for the redirect. Example: temporal,permanent
     * @queryParam type string Filter by specifying a status. Example: single,collection
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam term string Specifying a keyword similar to model name, target_url, or redirect_url. Example: https://somelink.test
     * @queryParam per_page string Overriding the default (10) number of listings per-page. Example: 20
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: created_at:desc
     *
     * @param RedirectListingQueryParamsRequest $request
     * @return JsonResponse
     */
    public function index(RedirectListingQueryParamsRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $redirects = (new CacheDataManager(
                $this->redirectDataService,
                'getPaginatedList',
                [$request]
            ))->getData();

            return $this->success('Redirects List', 200, [
                'redirects' => $redirects,
                'options' => [
                    ...ClientOptions::only('redirects', ['order_by', 'soft_delete', 'hard_delete', 'type']),
                    ...ClientOptions::only('general', ['order_direction', 'deleted']),
                ],
                'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Redirects))->getDefaultQueryParams()
            ]);
        } catch (NotFoundExceptionInterface $e) {
            return $this->error('No result(s) found.', 404);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e);
            return $this->error('An error occurred while fetching Redirects.', 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Redirects.', 400);
        }
    }

    /**
     * Redirect Options
     *
     * Get options for redirect creation.
     *
     * @group Redirects
     * @authenticated
     * @header Content-Type application/json
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return $this->success('Redirects Options', 200, [
            'options' => [
                ...ClientOptions::only('redirects', ['order_by', 'soft_delete', 'hard_delete', 'type']),
            ],
        ]);
    }

    /**
     * Create Redirect.
     *
     * Add a new redirect to the system.
     *
     * @group Redirects
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam target_url string required The target url for the redirect. Example: https://google.com
     * @bodyParam redirect_url string required The redirect url for the redirect. Example: https://google.com
     * @bodyParam soft_delete string required The soft delete status for the redirect. Example: temporal
     * @bodyParam hard_delete string required The hard delete status for the redirect. Example: temporal,permanent
     * @bodyParam type string required The type for the redirect. Example: single,collection
     * @bodyParam model object required The object representation of the entity being redirected. Example: {"name": "Event", "ref": "event_1"}
     *
     * @param StoreRedirectRequest $request
     * @return JsonResponse
     */
    public function store(StoreRedirectRequest $request): JsonResponse
    {
        try {
            $createdRedirects = [];
            $targetUrl = $request->validated('target_url');
            $path = parse_url($targetUrl, PHP_URL_PATH);

            if ($request->has('model')) {
                Redirect::updateOrCreate([
                    'redirectable_id' => $request->validated('model')['id'],
                    'redirectable_type' => get_class($request->validated('model'))
                ], [
                    'target_url' => $request->validated('target_url'),
                    'redirect_url' => $request->validated('redirect_url'),
                    'soft_delete' => $request->validated('soft_delete'),
                    'hard_delete' => $request->validated('hard_delete'),
                    'type' => $request->validated('type'),
                    'model' => $request->validated('model'),
                    'target_path' => $path,
                ]);
            } else {
                $validatedData = $request->validated();
                $validatedData['target_path'] = $path;
                $createdRedirects[] = Redirect::create($validatedData);
            }
            $this->redirectMongodbInsert($request->validated('target_url'));

            return $this->success('Redirect created successfully.', 201, [
                'redirects' => $createdRedirects,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
           // return $this->error('An error occurred while creating Redirect.', 400);
           return $this->error('An error occurred while creating Redirect: ' . $exception->getMessage(), 400);

        }
    }

    public function redirectMongodbInsert($target_url)
    {
        $redirects = DB::table('redirects')
            ->where('target_url', $target_url)
            ->first();
        $aws_credentials = config('services.ses');
        $tableName =  $aws_credentials['table_name'];
        $client = new DynamoDbClient([
            'region'  => $aws_credentials['region'],
            'version' => 'latest',
        ]);
        $marshaler = new Marshaler();
        $data = [
            'id' =>  $redirects->id,
            'site_id' =>  clientSiteId(),
            'redirect_url' => $redirects->redirect_url,
            'target_url' => $redirects->target_url,
            'target_path' =>  $redirects->target_path,
            'http_code' => 301,
            'active' =>  $redirects->is_active,
            'created_at' => $redirects->created_at,
            'updated_at' => $redirects->updated_at,
        ];
        $client->putItem([
            'TableName' => $tableName,
            'Item' => $marshaler->marshalItem($data),
        ]);
    }


    /**
     * Setup Multiple Entity Redirects.
     *
     * Add a redirect to multiple entities in the system.
     *
     * @group Redirects
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam models string[] required The array of redirect. Example: https://google.com
     *
     * @param StoreRedirectRequest $request
     * @return JsonResponse
     */
    public function storeMany(StoreRedirectRequest $request): JsonResponse
    {
        try {
            $createdRedirects = [];

            foreach ($request->validated('redirects') as $entity) {
                $createdRedirects[] = Redirect::create([
                    'target_url' => $entity['target_url'],
                    'redirect_url' => $entity['redirect_url'],
                    'soft_delete' => $entity['soft_delete'],
                    'hard_delete' => $entity['hard_delete'],
                    'type' => $entity['type'],
                    'model' => $entity['model'],
                    'redirectable_id' => $entity['model']['id'],
                    'redirectable_type' => get_class($entity['model']),
                ]);
            }
            $redirect = Redirect::create($request->validated());

            return $this->success('Redirect created successfully.', 201, [
                'redirect' => $redirect,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while creating Redirect.', 400);
        }
    }

    /**
     * Fetch Redirect
     *
     * Display the specified redirect.
     *
     * @group Redirects
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam redirect string required The ref of the redirect. Example: 9913d099-ae76-46c9-bb2b-eb6f266b2cbf
     *
     * @param string $redirect
     * @return JsonResponse
     */
    public function show(string $redirect): JsonResponse
    {
        try {
            $redirect = (new CacheDataManager(
                $this->redirectDataService,
                'show',
                [$redirect]
            ))->getData();

            return $this->success('Redirect details', 200, [
                'redirect' => $redirect,
                'options' => [
                    ...ClientOptions::only('redirects', ['order_by', 'soft_delete', 'hard_delete', 'type']),
                ],
            ]);
        } catch (ModelNotFoundException $e) {
//            Log::error($e);
            return $this->error('No result(s) found.', 404);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e);
            return $this->error('An error occurred while fetching Redirect.', 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching Redirect.', 400);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Redirect $redirect
     * @return \Illuminate\Http\Response
     */
    public function edit(Redirect $redirect)
    {
        //
    }

    /**
     * Update redirect
     *
     * Update the specified redirect.
     *
     * @group Redirects
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam target_url string The target url for the redirect. Example: https://google.com
     * @bodyParam redirect_url string The redirect url for the redirect. Example: https://google.com
     * @bodyParam soft_delete string The soft delete status for the redirect. Example: temporal
     * @bodyParam hard_delete string The hard delete status for the redirect. Example: temporal,permanent
     * @bodyParam type string The type for the redirect. Example: single,collection
     *
     * @param UpdateRedirectRequest $request
     * @param Redirect $redirect
     * @return JsonResponse
     */
    public function update(UpdateRedirectRequest $request, Redirect $redirect): JsonResponse
    {
        try {
            $aws_credentials = config('services.ses');
            $tableName =  $aws_credentials['table_name'];
            $client = new DynamoDbClient([
                'region'  => $aws_credentials['region'],
                'version' => 'latest',
            ]);
            $redirects = DB::table('redirects')
                ->where('ref', $request->ref)
                ->first();

            $marshaler = new Marshaler();
            $key = [
                'target_path' => $marshaler->marshalValue($redirects->target_path),
                'site_id' => $marshaler->marshalValue($redirects->site_id),
            ];
            $client->deleteItem([
                'TableName' => $tableName,
                'Key' => $key,
            ]);
            $targetUrl = $request->validated('target_url');
            $path = parse_url($targetUrl, PHP_URL_PATH);
            $validatedData = $request->validated();
            $validatedData['target_path'] = $path;
            $redirect->update($validatedData);

            $redirects_new = DB::table('redirects')
                ->where('ref', $request->ref)
                ->first();

            $data = [
                'id' =>  $redirects->id,
                'site_id' =>  clientSiteId(),
                'redirect_url' => $redirects_new->redirect_url,
                'target_url' => $redirects_new->target_url,
                'target_path' =>  $redirects_new->target_path,
                'http_code' => 301,
                'active' =>  $redirects_new->is_active,
                'created_at' => $redirects_new->created_at,
                'updated_at' => $redirects_new->updated_at,
            ];
            $client->putItem([
                'TableName' => $tableName,
                'Item' => $marshaler->marshalItem($data),
            ]);
            $this->clearCacheOfRelatedEntityDataService($redirect);

            return $this->success('Redirect updated successfully.', 200, [
                'redirect' => $redirect,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while updating Redirect.', 400);
        }
    }

    /**
     * Delete Redirects
     *
     * Remove multiple redirects by specified ids.
     *
     * @group Redirects
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam redirects_ids array required The ids of the redirects to be deleted. Example: [1,2,3]
     * @queryParam permanently string Optionally specifying to force-delete model, instead of the default soft-delete. Example: 1
     *
     * @param DeleteRedirectsRequest $request
     * @return JsonResponse
     */
    public function destroy(DeleteRedirectsRequest $request): JsonResponse
    {
        try {
            $force = (request('permanently') == 1);
            $response = (new SoftDeleteableManagementService(Redirect::class))
                ->delete($request->validated('redirects_ids'), 'permanently');

            $aws_credentials = config('services.ses');
            $tableName =  $aws_credentials['table_name'];
            $client = new DynamoDbClient([
                'region'  => $aws_credentials['region'],
                'version' => 'latest',
            ]);
            $redirects_ids = $request->validated('redirects_ids');
            if (!empty($redirects_ids)) {
                $redirects = DB::table('redirects')
                    ->whereIn('id', $redirects_ids)
                    ->get();

                $marshaler = new Marshaler();
                foreach ($redirects as $redirect) {
                    $key = [
                        'target_path' => $marshaler->marshalValue($redirect->target_path),
                        'site_id' => $marshaler->marshalValue($redirect->site_id),
                    ];
                    $client->deleteItem([
                        'TableName' => $tableName,
                        'Key' => $key,
                    ]);
                }
            }

            CacheDataManager::flushAllCachedServiceListings($this->redirectDataService);

            return $this->success('Redirect(s) has been '. ($force ? 'permanently ' : null) . 'deleted.');
        } catch (DeletionConfirmationRequiredException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode(), $exception->payload);
        } catch (InvalidSignatureForHardDeletionException $exception) {
            Log::error($exception);

            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while deleting Redirect(s).', 400);
        }
    }

    /**
     * Restore Many Redirects
     *
     * Restore multiple redirects data by specifying their ids.
     *
     * @group Redirects
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam redirects_ids string[] required The list of ids associated with redirects. Example: [1,2]
     *
     * @param RestoreRedirectsRequest $request
     * @return JsonResponse
     */
    public function restore(RestoreRedirectsRequest $request): JsonResponse
    {
        try {
            $response = (new SoftDeleteableManagementService(Redirect::class))
                ->restore($request->validated('redirects_ids'));

            CacheDataManager::flushAllCachedServiceListings($this->redirectDataService);

            return $this->success('Specified redirect(s) has been restored.', 200, [
                'redirects' => (new CacheDataManager(
                    $this->redirectDataService,
                    'getPaginatedList',
                    [$request],
                    true
                ))->getData(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while restoring specified redirect(s).', 400);
        }
    }

    /**
     * @param Redirect $redirect
     * @return void
     */
    private function clearCacheOfRelatedEntityDataService(Redirect $redirect): void
    {
        if (! is_null($type = $redirect->redirectable_type)) {
            $class = "App\Services\DataServices\\" . class_basename($type) . 'DataService';

            if (class_exists($class)) {
                CacheDataManager::flushAllCachedServiceListings(new $class);
            }
        }
    }
}
