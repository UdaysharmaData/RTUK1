<?php

namespace App\Modules\Event\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Traits\Response;
use App\Enums\ListTypeEnum;
use App\Facades\ClientOptions;
use App\Http\Helpers\AccountType;
use App\Modules\Event\Models\Serie;
use App\Enums\OrderByDirectionEnum;
use App\Http\Controllers\Controller;
use App\Services\DefaultQueryParamService;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\SerieDataService;
use App\Modules\Event\Requests\SerieUpdateRequest;
use App\Modules\Event\Requests\SerieCreateRequest;
use App\Modules\Event\Requests\SeriesDeleteRequest;
use App\Modules\Event\Requests\SeriesRestoreRequest;
use App\Enums\EventPropertyServicesListOrderByFieldsEnum;
use App\Http\Requests\DefaultListingQueryParamsRequest;
use App\Modules\Event\Requests\SerieListingQueryParamsRequest;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Traits\DraftCustomValidator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group Series
 *
 * Manages series.
 *
 * @header Content-Type application/json
 * @authenticated
 */
class SerieController extends Controller
{
    use Response, DraftCustomValidator;

    public function __construct(protected SerieDataService $serieDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_series', [
            'except' => [
                'all'
            ]
        ]);
    }

    /**
     * Paginated series for dropdown fields.
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param DefaultListingQueryParamsRequest $request
     * @return JsonResponse
     */
    public function all(DefaultListingQueryParamsRequest $request): JsonResponse
    {
        try {
            $series = (new CacheDataManager(
                $this->serieDataService,
                'all',
                [$request]
            ))->getData();

            return $this->success('List of series', 200, [
                'series' => $series
            ]);
        } catch (NotFoundExceptionInterface $e) {
            Log::error($e);
            return $this->error('Unable to apply filter(s)', 400);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e);
            return $this->error('An error occurred while fetching series', 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while getting serie list.', 404);
        }
    }

    /**
     * The list of series.
     *
     * @queryParam drafted string Specifying how to interact with drafted items. Example: with
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     *
     * @param  SerieListingQueryParamsRequest  $request
     * @return JsonResponse
     */
    public function index(SerieListingQueryParamsRequest $request): JsonResponse
    {
        try {
            $series = (new CacheDataManager(
                $this->serieDataService->removeRelations(['site']),
                'index',
                [$request]
            ))->getData();

            return $this->success('List of series', 200, [
                'series' => $series,
                'options' => ClientOptions::all('series'),
                'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::EventPropertyServices))
                    ->setParams(['order_by' => EventPropertyServicesListOrderByFieldsEnum::Name->value . ":" . OrderByDirectionEnum::Ascending->value])
                    ->getDefaultQueryParams(),
                'action_messages' => Serie::$actionMessages
            ]);
        } catch (NotFoundExceptionInterface $e) {
            Log::error($e);
            return $this->error('Unable to apply filter(s)', 400);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e);
            return $this->error('An error occurred while fetching series', 400);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while fetching series', 400);
        }
    }

    /**
     * Serie details.
     *
     * @urlParam serie_ref string required The ref of the serie. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string $ref
     * @return JsonResponse
     */
    public function show(string $ref): JsonResponse
    {
        try {
            $serie = (new CacheDataManager(
                $this->serieDataService->setRelations(['site']),
                'show',
                [$ref]
            ))->getData();
        } catch (ModelNotFoundException $e) {
            return $this->error('The serie was not found!', 404);
        }

        return $this->success('The serie details', 200, [
            'serie' => $serie
        ]);
    }

    /**
     * Create a serie.
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        try {
            $sites = $this->serieDataService->sites();
        } catch (\Exception $exception) {
            return $this->error('An error occurred while creating list.', 404);
        }

        return $this->success('Create a serie', 200, [
            'sites' => $sites
        ]);
    }

    /**
     * Store a serie
     *
     * @param  SerieCreateRequest $request
     * @return JsonResponse
     */
    public function store(SerieCreateRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $serie = $this->serieDataService->store($request);

            DB::commit();
        } catch (QueryException | \Exception $e) {
            DB::rollback();

            return $this->error('Unable to create the serie! Please try again', 406, $e->getMessage());
        }

        return $this->success('Series created successfully', 201, [
            'serie' => $serie
        ]);
    }

    /**
     * Edit a serie.
     *
     * @urlParam serie_ref string required The ref of the serie. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param string $ref
     * @return JsonResponse
     */
    public function edit(string $ref)
    {
        try {
            $serie = (new CacheDataManager(
                $this->serieDataService->setRelations(['site']),
                'edit',
                [$ref]
            ))->getData();
        } catch (ModelNotFoundException $e) {
            return $this->error('The serie was not found!', 404);
        }

        $sites = $this->serieDataService->sites();

        return $this->success('Edit the serie', 200, [
            'sites' => $sites,
            'serie' => $serie,
            'action_messages' => Serie::$actionMessages
        ]);
    }


    /**
     * Mark as published one or many series
     *
     * @bodyParam ids string[] required An array list of ids associated with series. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsPublished(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsPublishedValidationRules('series'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();

            $this->serieDataService->markAsPublished($request->ids);

            DB::commit();

            return $this->success('Successfully marked as published the serie(s)!', 200);
        } catch (\Exception $e) {
            DB::rollback();

            return $this->error('Unable to mark as published the serie(s)! Please try again.', 406, $e->getMessage());
        }
    }

    /**
     * Mark as draft one or many series
     *
     * @bodyParam ids string[] required An array list of ids associated with series. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsDraft(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsDraftValidationRules('series'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();

            $this->serieDataService->markAsDraft($request->ids);

            DB::commit();

            return $this->success('Successfully marked as draft the serie(s)!', 200);
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to mark as draft the serie(s)! Please try again.', 406, $e->getMessage());
        }
    }


    /**
     * Update serie
     *
     * @param  SerieUpdateRequest $request
     * @param  string $ref
     * @return JsonResponse
     */
    public function update(SerieUpdateRequest $request, string $ref): JsonResponse
    {
        try {
            DB::beginTransaction();

            $serie = $this->serieDataService->update($request, $ref);

            DB::commit();
        } catch (QueryException | \Exception $e) {
            DB::rollback();

            return $this->error('Unable to update the serie! Please try again', 406, $e->getMessage());
        }

        return $this->success('Series updated successfully', 200, [
            'serie' => $serie
        ]);
    }

    /**
     * Delete one or many series
     *
     * @bodyParam ids string[] required An array list of ids associated with series. Example: [1,2]
     *
     * @param SeriesDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(SeriesDeleteRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $this->serieDataService->destroy($request->validated('ids'));

            DB::commit();

        } catch (QueryException | \Exception $e) {
            DB::rollback();

            return $this->error('Unable to delete the serie! Please try again', 406, $e->getMessage());
        }

        return $this->success('Successfully deleted the serie(s)', 200);
    }

    /**
     * Restore one or many series
     *
     * bodyParam ids string[] required An array list of ids associated with series. Example: [1,2]
     *
     * @param  mixed $request
     * @return JsonResponse
     */
    public function restore(SeriesRestoreRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $this->serieDataService->restore($request->validated('ids'));

            DB::commit();

        } catch (QueryException | \Exception $e) {
            DB::rollback();

            return $this->error('Unable to restore the serie(s)! Please try again', 406, $e->getMessage());
        }

        return $this->success('Successfully restored the serie(s)', 200);
    }

    /**
     * Delete one or many series permanently
     * Only the administrator can delete a serie permanently.
     *
     * @bodyParam ids string[] required An array list of ids associated with series. Example: [1,2]
     *
     * @param  SeriesDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(SeriesDeleteRequest $request): JsonResponse
    {
        if (!AccountType::isAdmin()) { // Only the administrator can delete an event permanently.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        try {
            DB::beginTransaction();

            $this->serieDataService->destroyPermanently($request->validated('ids'));

            DB::commit();

        } catch (QueryException | \Exception $e) {
            DB::rollback();

            return $this->error('Unable to delete the serie! Please try again', 406, $e->getMessage());
        }

        return $this->success('Successfully deleted the serie(s)', 200);
    }

    /**
     * Export Series
     *
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     *
     * @param SerieListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(SerieListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->serieDataService->setRelations([])->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to export medals.', 400, $exception->getMessage());
        }
    }
}
