<?php

namespace App\Http\Controllers;

use App\Enums\AudienceSourceEnum;
use App\Enums\ListTypeEnum;
use App\Enums\MetaRobotsEnum;
use App\Facades\ClientOptions;
use App\Http\Requests\AudienceListingQueryParamsRequest;
use App\Http\Requests\DeleteAudienceMailingListsRequest;
use App\Http\Requests\DeleteAudiencesRequest;
use App\Http\Requests\RestoreAudienceMailingListsRequest;
use App\Http\Requests\RestoreAudiencesRequest;
use App\Http\Requests\StoreAudienceRequest;
use App\Http\Requests\UpdateAudienceMailingListRequest;
use App\Http\Requests\UpdateAudienceRequest;
use App\Models\Audience;
use App\Models\MailingList;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\AudienceDataService;
use App\Services\DefaultQueryParamService;
use App\Services\FileManager\FileManager;
use App\Services\SoftDeleteable\Exceptions\DeletionConfirmationRequiredException;
use App\Services\SoftDeleteable\Exceptions\InvalidSignatureForHardDeletionException;
use App\Services\SoftDeleteable\SoftDeleteableManagementService;
use App\Traits\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AudienceController extends Controller
{
    use Response;

    public function __construct(protected AudienceDataService $audienceDataService)
    {
        parent::__construct();
    }

    /**
     * Get Audience
     *
     * Get paginated list of audiences.
     *
     * @group Audience
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam term string Specifying a keyword similar to audience name, or description. Example: audience name
     * @queryParam source string Specifying the source attribute of the audience entity. Example: emails
     * @queryParam author string Specifying the author role. Example: administrator
     * @queryParam per_page string Overriding the default (10) number of listings per-page. Example: 20
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     *
     * @param AudienceListingQueryParamsRequest $request
     * @return JsonResponse
     */
    public function index(AudienceListingQueryParamsRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $audiences = (new CacheDataManager(
                $this->audienceDataService,
                'getPaginatedList',
                [$request]
            ))->getData();

            return $this->success('Audiences List', 200, [
                'audiences' => $audiences,
                'options' => [
                    ...ClientOptions::only('general', ['order_direction', 'deleted']),
                    ...ClientOptions::only('audiences', ['source', 'author', 'order_by'])
                ],
                'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Audiences))->getDefaultQueryParams()
            ]);
        } catch (NotFoundExceptionInterface $e) {
            Log::error($e);
            return $this->error('No result(s) found.', 400);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e);
            return $this->error('An error occurred while fetching audiences.', 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching audiences.', 400);
        }
    }

    /**
     * Fetch Audience Options
     *
     * Retrieve audience creation options data.
     *
     * @group Audience
     * @authenticated
     * @header Content-Type application/json
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return $this->success('Audience options retrieved.', 200, [
            'options' => [
                ...ClientOptions::only('audiences', ['source']),
                'robots' => MetaRobotsEnum::_options()
            ]
        ]);
    }

    /**
     * Create a new Audience
     *
     * New audiences can be created with optional.
     *
     * @group Audiences
     * @authenticated
     * @header Content-Type application/json
     *
     * @param StoreAudienceRequest $request
     * @return JsonResponse
     */
    public function store(StoreAudienceRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $audience = DB::transaction(function () use ($request) {
                $audience = Audience::create($request->only(['name', 'description', 'source']));
                $this->audienceDataService->processAudienceMailingList($request, $audience);

                return $audience;
            });

            CacheDataManager::flushAllCachedServiceListings($this->audienceDataService);

            return $this->success('New Audience Created.', 201, [
                'audience' => $audience->load(array_filter([
                    'author',
                    $this->audienceDataService->getAudienceDynamicRelation($request->get('source'))
                ]))
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while creating audience.', 400);
        }
    }

    /**
     * Fetch Audience
     *
     * Retrieve audience data matching specified ref attribute.
     *
     * @group Audience
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam ref string required The ref attribute of the page. Example: 97f62d3d-bf9d-42ac-88c4-9d56cd910d7a
     *
     * @param string $ref
     * @return JsonResponse
     */
    public function show(string $ref): JsonResponse
    {
        try {
            $audience = (new CacheDataManager(
                $this->audienceDataService,
                'show',
                [$ref]
            ))->getData();

            return $this->success('Audience data retrieved.', 200, [
                ...$audience,
                'options' => [
                    'robots' => MetaRobotsEnum::_options()
                ]
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error('Audience not found.', 404);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching audience.', 400);
        }
    }

    /**
     * Fetch Audience Options - portal
     *
     * Retrieve audience creation options data.
     *
     * @group Audience
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam ref string required The ref attribute of the page. Example: 97f62d3d-bf9d-42ac-88c4-9d56cd910d7a
     *
     * @param string $ref
     * @return JsonResponse
     */
    public function edit(string $ref): JsonResponse
    {
        try {
            $audience = (new CacheDataManager(
                $this->audienceDataService,
                'show',
                [$ref]
            ))->getData();

            return $this->success('Audience data retrieved.', 200, [
                ...$audience,
                'options' => [
                    ...ClientOptions::only('audiences', ['source']),
                    'robots' => MetaRobotsEnum::_options()
                ]
            ]);
        } catch (ModelNotFoundException $exception) {
            Log::error($exception);
            return $this->error('Audience not found.', 404);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching audience.', 400);
        }
    }

    /**
     * Update an Audience
     *
     * An existing audience can be updated.
     *
     * @group Audiences
     * @authenticated
     * @header Content-Type application/json
     *
     * @param UpdateAudienceRequest $request
     * @param Audience $audience
     * @return JsonResponse
     */
    public function update(UpdateAudienceRequest $request, Audience $audience): \Illuminate\Http\JsonResponse
    {
        try {
            $audience = DB::transaction(function () use ($audience, $request) {
                $audience->update($request->only(['name', 'description', 'source']));
                $this->audienceDataService->processAudienceMailingList($request, $audience);

                return $audience;
            });

            CacheDataManager::flushAllCachedServiceListings($this->audienceDataService);

            return $this->success('Audience Updated.', 201, [
                'audience' => $audience->load(array_filter([
                    'author',
                    $this->audienceDataService->getAudienceDynamicRelation($audience->source?->value)
                ]))
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while updating audience.', 400);
        }
    }

    /**
     * Delete Audiences
     *
     * Delete multiple audiences data by specifying their ids.
     *
     * @group Audience
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam audiences_ids string[] required The list of ids associated with pages. Example: [1,2]
     * @queryParam permanently string Optionally specifying to force-delete model, instead of the default soft-delete. Example: 1
     *
     * @param DeleteAudiencesRequest $request
     * @return JsonResponse
     */
    public function destroy(DeleteAudiencesRequest $request): JsonResponse
    {
        try {
            $force = (request('permanently') == 1);
            $response = (new SoftDeleteableManagementService(Audience::class))
                ->delete($request->validated('audiences_ids'), 'permanently');

            CacheDataManager::flushAllCachedServiceListings($this->audienceDataService);

            return $this->success('Audience(s) '. ($force ? 'permanently ' : null) . 'deleted.', 200, [
                'audiences' => (new CacheDataManager(
                    $this->audienceDataService,
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

            return $this->error('An error occurred while deleting specified audience(s).', 400);
        }
    }

    /**
     * Restore Many Audiences
     *
     * Restore multiple audiences data by specifying their ids.
     *
     * @group Audience
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam audiences_ids string[] required The list of ids associated with audiences. Example: [1,2]
     *
     * @param RestoreAudiencesRequest $request
     * @return JsonResponse
     */
    public function restore(RestoreAudiencesRequest $request): JsonResponse
    {
        try {
            $response = (new SoftDeleteableManagementService(Audience::class))
                ->restore($request->validated('audiences_ids'));

            CacheDataManager::flushAllCachedServiceListings($this->audienceDataService);

            return $this->success('Specified audience(s) has been restored.', 200, [
                'audiences' => (new CacheDataManager(
                    $this->audienceDataService,
                    'getPaginatedList',
                    [$request],
                    true
                ))->getData(),
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while restoring specified audience(s).', 400);
        }
    }

    /**
     * Update Audience's Mailing List
     *
     * An existing audience's mailing list can be updated.
     *
     * @group Audiences
     * @authenticated
     * @header Content-Type application/json
     *
     * @param UpdateAudienceMailingListRequest $request
     * @param Audience $audience
     * @param MailingList $mailingList
     * @return JsonResponse
     */
    public function updateMailingList(UpdateAudienceMailingListRequest $request, Audience $audience, MailingList $mailingList): \Illuminate\Http\JsonResponse
    {
        try {
            $mailingList->update($request->only(['first_name', 'last_name', 'phone', 'email']));

            CacheDataManager::flushAllCachedServiceListings($this->audienceDataService);

            $audience = (new CacheDataManager(
                $this->audienceDataService,
                'getAudienceRelations',
                [$audience]
            ))->getData();

            return $this->success('Mailing list Updated.', 201, [
                ...$audience
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while updating mailing list.', 400);
        }
    }

    /**
     * Delete Audiences' Mailing List Items
     *
     * Delete audiences' mailing list data by specifying their ids.
     *
     * @group Audience
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam mailing_lists_ids string[] required The list of ids associated with mailing_lists. Example: [1,2]
     * @queryParam permanently string Optionally specifying to force-delete model, instead of the default soft-delete. Example: 1
     *
     * @param DeleteAudienceMailingListsRequest $request
     * @param Audience $audience
     * @return JsonResponse
     */
    public function destroyMailingLists(DeleteAudienceMailingListsRequest $request, Audience $audience): JsonResponse
    {
        try {
            $force = (request('permanently') == 1);
            $response = (new SoftDeleteableManagementService(MailingList::class))
                ->delete($request->validated('mailing_lists_ids'), 'permanently');

            CacheDataManager::flushAllCachedServiceListings($this->audienceDataService);

            $audience = (new CacheDataManager(
                $this->audienceDataService,
                'getAudienceRelations',
                [$audience]
            ))->getData();

            return $this->success('Mailing list item(s) '. ($force ? 'permanently ' : null) . 'deleted.', 200, [
                ...$audience
            ]);
        } catch (DeletionConfirmationRequiredException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode(), $exception->payload);
        } catch (InvalidSignatureForHardDeletionException $exception) {
            Log::error($exception);

            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->error('An error occurred while deleting specified items.', 400);
        }
    }

    /**
     * Restore Audiences' Mailing List Items
     *
     * Restore audiences' mailing list data by specifying their ids.
     *
     * @group Audience
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam mailing_lists_ids string[] required The list of ids associated with mailing_lists. Example: [1,2]
     *
     * @param RestoreAudienceMailingListsRequest $request
     * @param Audience $audience
     * @return JsonResponse
     */
    public function restoreMailingLists(RestoreAudienceMailingListsRequest $request, Audience $audience): JsonResponse
    {
        try {
            $response = (new SoftDeleteableManagementService(MailingList::class))
                ->restore($request->validated('mailing_lists_ids'));

            CacheDataManager::flushAllCachedServiceListings($this->audienceDataService);

            $audience = (new CacheDataManager(
                $this->audienceDataService,
                'getAudienceRelations',
                [$audience]
            ))->getData();

            return $this->success('Specified mailing list item(s) has been restored.', 200, [
                ...$audience
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while restoring specified mailing list item(s).', 400);
        }
    }
}
