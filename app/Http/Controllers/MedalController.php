<?php

namespace App\Http\Controllers;

use App\Enums\ListTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\MedalsListOrderByFieldsEnum;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Models\Medal;
use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Enums\MedalTypeEnum;
use App\Traits\DownloadTrait;
use App\Facades\ClientOptions;
use App\Http\Helpers\AccountType;
use App\Modules\Event\Models\Event;
use App\Enums\OrderByDirectionEnum;
use App\Modules\Setting\Models\Site;
use App\Http\Resources\MedalResource;
use App\Http\Requests\MedalCreateRequest;
use App\Http\Requests\MedalDeleteRequest;
use App\Http\Requests\MedalUpdateRequest;
use App\Http\Requests\MedalRestoreRequest;
use App\Services\DefaultQueryParamService;
use App\Modules\Event\Models\EventCategory;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\MedalDataService;
use App\Http\Requests\MedalListingQueryParamsRequest;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\FileManager\Traits\UploadModelTrait;
use App\Traits\DraftCustomValidator;
use Illuminate\Support\Facades\Validator;

/**
 * @group Medals
 *
 * APIs for managing medals
 * @authenticated
 */
class MedalController extends Controller
{
    use Response, SiteTrait, UploadModelTrait, DownloadTrait, DraftCustomValidator;

    /*
    /@group Medals

    |--------------------------------------------------------------------------
    | Medal Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with medals. That is
    | the creation, view, update, delete and more ...
    |
    */

    public function __construct(protected MedalDataService $medalDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_medals');
    }

    /**
     * The list of Medals
     *
     * @queryParam category string Filter by event category slug. Example: No-example
     * @queryParam event string Filter by event slug. Example: No-example
     * @queryParam drafted string Specifying how to interact with drafted items. Example: with
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam term string Filter by term. The term to search for. Example: No-example
     * @queryParam page integer The page data to return. Example: 1
     * @queryParam per_page integer Items per page . Example: No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,type:asc,created_at:desc
     * @queryParam type string Filter by medal type. Example: default
     *
     * @param MedalListingQueryParamsRequest $request
     * @return JsonResponse
     */
    public function index(MedalListingQueryParamsRequest $request): JsonResponse
    {
        try {
            $medals = (new CacheDataManager(
                $this->medalDataService,
                'getPaginatedList',
                [$request]
            ))->getData();
        } catch (NotFoundExceptionInterface $e) {
            Log::error($e->getMessage());
            return $this->error('Unable to apply filter(s)', 400);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e->getMessage());
            return $this->error('An error occurred while fetching medals', 400);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->error('An error occurred while fetching medals', 400);
        }

        return $this->success(
            'List of medals',
            200,
            [
                'medals' => new MedalResource($medals),
                'options' => ClientOptions::all('medals'),
                'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Medals))
                    ->setParams(['order_by' => MedalsListOrderByFieldsEnum::Name->value . ':' . OrderByDirectionEnum::Ascending->value])
                    ->getDefaultQueryParams(),
                'action_messages' => Medal::$actionMessages
            ]
        );
    }

    /**
     * Show meadal details
     *
     * @urlParam medal required The ref of the medal Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param string $ref
     * @return JsonResponse
     */
    public function show(string $ref): JsonResponse
    {
        try {
            $medal = (new CacheDataManager(
                $this->medalDataService,
                'getMedalByRef',
                [$ref]
            ))->getData();

            return $this->success('Medal details', 200, [
                'medal' => (new MedalResource($medal))
            ]);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Medal not found', 404);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to retrieve medal.', 400);
        }
    }

    /**
     * Create a medal
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        $sites = Site::select('ref', 'name', 'domain')->hasAccess()->makingRequest()->get();

        return $this->success('Create a medal', 200, [
            'types' => MedalTypeEnum::_options(),
            'sites' => $sites
        ]);
    }

    /**
     * Store a medal
     *
     * @param MedalCreateRequest $request
     * @return JsonResponse
     */
    public function store(MedalCreateRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $site = Site::where('ref', $request->get('site'))->first();

            $medal = new Medal();
            $medal->fill($request->only(['type', 'name', 'description',]));
            $medal->site()->associate($site);
            $medal = $this->assignMedalable($medal);
            $medal->save();

            if ($request->filled('image')) {
                $this->attachSingleUploadToModel($medal, $request->image);
            }
            DB::commit();

            return $this->success('Successfully created medal.', 200, [
                'medal' => (new MedalResource($medal->load('upload')))
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to create medal.', 400);
        }
    }

    /**
     * Edit a medal
     *
     * @urlParam medal_ref required The ref of the medal Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string $ref
     * @return JsonResponse
     */
    public function edit(string $ref): JsonResponse
    {
        try {
            $medal = (new CacheDataManager(
                $this->medalDataService,
                'getMedalByRef',
                [$ref]
            ))->getData();
            $sites = Site::select('ref', 'name', 'domain')->hasAccess()->makingRequest()->get();

            return $this->success('Medal retrieved', 200, [
                'medal' => (new MedalResource($medal)),
                'types' => MedalTypeEnum::_options(),
                'sites' =>  $sites,
                'action_messages' => Medal::$actionMessages
            ]);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Medal not found', 404);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to retrieve medal.', 400);
        }
    }

    /**
     * Update a medal
     *
     * @urlParam medal_ref required The ref of the medal. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  MedalUpdateRequest   $request
     * @param  string $ref
     * @return JsonResponse
     */
    public function update(MedalUpdateRequest $request, string $ref): JsonResponse
    {
        try {
            $medal = MedalDataService::customSelectsQuery()
            ->withDrafted()
            ->filterBySite()
            ->where('ref', $ref)
            ->firstOrFail();

            DB::beginTransaction();

            $site = Site::where('ref', $request->get('site'))->first();

            $medal->fill($request->only(['type', 'name', 'description']));
            $medal->site()->associate($site);
            $medal = $this->assignMedalable($medal);
            $medal->save();

            DB::commit();

            if ($request->filled('image')) {
                $this->attachSingleUploadToModel($medal, $request->image);
            }

            CacheDataManager::flushAllCachedServiceListings($this->medalDataService);

            return $this->success('Successfully updated medal.', 200, [
                'medal' => (new MedalResource($medal))
            ]);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Medal not found', 404);
        }
    }

    /**
     * Mark one or many medals as published
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function markAsPublished(Request $request)
    {
        $validator = Validator::make($request->all(), $this->markAsPublishedValidationRules('medals'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            Medal::onlyDrafted()->filterBySite()->whereIntegerInRaw('id', $request->ids)->markAsPublished();

           CacheDataManager::flushAllCachedServiceListings($this->medalDataService);

           return $this->success('Medal(s) marked as published', 200);
       } catch (\Exception $exception) {
           return $this->error('An error occurred while trying to restore medal.', 400);
       }
    }

    /**
     * Mark one or many medals as draft
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function markAsDraft(Request $request)
    {
        $validator = Validator::make($request->all(), $this->markAsDraftValidationRules('medals'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            Medal::filterBySite()->whereIntegerInRaw('id', $request->ids)->markAsDraft();

           CacheDataManager::flushAllCachedServiceListings($this->medalDataService);

           return $this->success('Medal(s) marked as draft', 200);
       } catch (\Exception $exception) {
           return $this->error('An error occurred while trying to restore medal.', 400);
       }
    }

    /**
     * Delete one or many medals
     *
     * @param  MedalDeleteRequest  $request
     * @return JsonResponse
     */
    public function destroy(MedalDeleteRequest $request): JsonResponse
    {
        try {
            Medal::filterBySite()->withDrafted()->whereIn('ref', $request->refs)->delete();

            CacheDataManager::flushAllCachedServiceListings($this->medalDataService);

            return $this->success('Medal(s) deleted', 200);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Medal not found', 404);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to delete medal.', 400);
        }
    }

    /**
     * Restore one or many medals
     *
     * @param  MedalRestoreRequest $request
     * @return JsonResponse
     */
    public function restoreMany(MedalRestoreRequest $request): JsonResponse
    {
        try {
             Medal::onlyTrashed()->withDrafted()->filterBySite()->whereIn('ref', $request->refs)->restore();

            CacheDataManager::flushAllCachedServiceListings($this->medalDataService);

            return $this->success('Medal(s) restored', 200);
        } catch (ModelNotFoundException $exception) {
            return $this->error('Medal not found', 404);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to restore medal.', 400);
        }
    }


    /**
     * Delete One or Many medals permanently
     * Only the administrator can delete a medal permanently
     *
     * @param  MedalDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermenantly(MedalDeleteRequest $request): JsonResponse
    {
        try {

            if (!AccountType::isAdmin()) { // Only the administrator can delete a medal permanently.
                return $this->error('You do not have permission to access this resource!', 403);
            }

            Medal::onlyTrashed()->withDrafted()->filterBySite()->whereIn('ref', $request->refs)->get()->each(function ($medal) {
                $medal->forceDelete();
            });

            return $this->success('Medal(s) permenanly deleted');
        } catch (ModelNotFoundException $exception) {
            return $this->error('Medal not found', 404);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to permenanly delete medal.', 400);
        }
    }

    /**
     * Export medals
     *
     * @queryParam category string Filter by event category slug. Examplete: No-example
     * @queryParam event string Filter by event slug. Examplete: No-example
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam term string Filter by term. The term to search for. Examplete: No-example
     * @queryParam type string Filter by medal type. Examplete: No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,type:asc,created_at:desc
     *
     * @param MedalListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(MedalListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->medalDataService->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to export medals.', 400, $exception->getMessage());
        }
    }

    /**
     * @param Medal $medal
     * @return Medal
     */
    private function assignMedalable(Medal $medal): Medal
    {
        if (request()->filled('event')) {
            $medalable = Event::where('ref', request()->event)->withoutRelations();
        } else if (request()->filled('category')) {
            $medalable = EventCategory::where('ref', request()->category);
        }

        $medalable = $medalable->withoutAppends()->select('id', 'ref', 'name', 'slug')->first();

        $medal->medalable()->associate($medalable);

        return $medal;
    }
}
