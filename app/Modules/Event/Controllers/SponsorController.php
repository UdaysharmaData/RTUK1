<?php

namespace App\Modules\Event\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Traits\Response;
use App\Enums\ListTypeEnum;
use App\Facades\ClientOptions;
use App\Enums\OrderByDirectionEnum;
use App\Http\Controllers\Controller;
use App\Modules\Event\Models\Sponsor;
use App\Services\DefaultQueryParamService;
use App\Services\DataCaching\CacheDataManager;
use App\Modules\Event\Requests\SponsorUpdateRequest;
use App\Modules\Event\Requests\SponsorCreateRequest;
use App\Modules\Event\Requests\SponsorsDeleteRequest;
use App\Modules\Event\Requests\SponsorsRestoreRequest;
use App\Enums\EventPropertyServicesListOrderByFieldsEnum;
use App\Http\Requests\DefaultListingQueryParamsRequest;
use App\Modules\Event\Requests\SponsorListingQueryParamsRequest;
use App\Services\DataServices\SponsorDataService;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Traits\DraftCustomValidator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @group Sponsors
 *
 * Manages sponsors.
 *
 * @header Content-Type application/json
 * @authenticated
 */
class SponsorController extends Controller
{
    use Response, DraftCustomValidator;

    public function __construct(protected SponsorDataService $sponsorDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_sponsors', [
            'except' => [
                'all'
            ]
        ]);
    }

    /**
     * Paginated sponsors for dropdown fields.
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
            $sponsors = (new CacheDataManager(
                $this->sponsorDataService,
                'all',
                [$request]
            ))->getData();
        } catch (NotFoundExceptionInterface $e) {
            Log::error($e->getMessage());
            return $this->error('Unable to apply filter(s)', 400);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e->getMessage());
            return $this->error('An error occurred while fetching sponsors', 400);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while getting sponsor list.', 404);
        }

        return $this->success('List of sponsors', 200, [
            'sponsors' => $sponsors
        ]);
    }

    /**
     * The list of sponsors.
     *
     * @queryParam drafted string Specifying how to interact with drafted items. Example: with
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     *
     * @param  SponsorListingQueryParamsRequest  $request
     * @return JsonResponse
     */
    public function index(SponsorListingQueryParamsRequest $request): JsonResponse
    {
        try {
            $sponsors =  (new CacheDataManager(
                $this->sponsorDataService->removeRelations(['site']),
                'index',
                [$request]
            ))->getData();
        } catch (NotFoundExceptionInterface $e) {
            Log::error($e->getMessage());
            return $this->error('Unable to apply filter(s)', 400);
        } catch (ContainerExceptionInterface $e) {
            Log::error($e->getMessage());
            return $this->error('An error occurred while fetching sponsors', 400);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while getting sponsor list.', 404);
        }

        return $this->success('List of sponsors', 200, [
            'sponsors' => $sponsors,
            'options' => ClientOptions::all('sponsors'),
            'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::EventPropertyServices))
                ->setParams(['order_by' => EventPropertyServicesListOrderByFieldsEnum::Name->value . ":" . OrderByDirectionEnum::Ascending->value])
                ->getDefaultQueryParams(),
            'action_messages' => Sponsor::$actionMessages
        ]);
    }

    /**
     * Sponsor details.
     *
     * @urlParam sponsor_ref string required The ref of the sponsor. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string $ref
     * @return JsonResponse
     */
    public function show(string $ref): JsonResponse
    {
        try {
            $sponsor = $this->sponsorDataService->show($ref);
        } catch (ModelNotFoundException $e) {
            return $this->error('The sponsor was not found!', 404);
        }

        return $this->success('The sponsor details', 200, [
            'sponsor' => $sponsor
        ]);
    }

    /**
     * Create a sponsor.
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        try {
            $sites = $this->sponsorDataService->sites();
        } catch (\Exception $exception) {
            return $this->error('An error occurred while creating list.', 404);
        }

        return $this->success('Create Sponsor', 200, [
            'sites' => $sites
        ]);
    }

    /**
     * Store a sponsor
     *
     * @param  SponsorCreateRequest $request
     * @return JsonResponse
     */
    public function store(SponsorCreateRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $sponsor = $this->sponsorDataService->store($request);

            DB::commit();
        } catch (QueryException | \Exception $e) {
            DB::rollback();

            return $this->error('Unable to create the sponsor! Please try again', 406, $e->getMessage());
        }

        return $this->success('Sponsor was successfully created ', 201, [
            'sponsor' => $sponsor
        ]);
    }

    /**
     * Edit a sponsor.
     *
     * @urlParam sponsor_ref string required The ref of the sponsor. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param string $ref
     * @return JsonResponse
     */
    public function edit(string $ref)
    {
        try {
            $sponsor = (new CacheDataManager(
                $this->sponsorDataService,
                'edit',
                [$ref]
            ))->getData();
        } catch (ModelNotFoundException $e) {
            return $this->error('The sponsor was not found!', 404);
        }

        $sites = $this->sponsorDataService->sites();

        return $this->success('Edit the sponsor', 200, [
            'sites' => $sites,
            'sponsor' => $sponsor,
            'action_messages' => Sponsor::$actionMessages
        ]);
    }


    /**
     * Update sponsor
     *
     * @param  SponsorUpdateRequest $request
     * @param  string $ref
     * @return JsonResponse
     */
    public function update(SponsorUpdateRequest $request, string $ref): JsonResponse
    {
        try {
            DB::beginTransaction();

            $sponsor = $this->sponsorDataService->update($request, $ref);

            DB::commit();
        } catch (QueryException | \Exception $e) {
            DB::rollback();

            return $this->error('Unable to update the sponsor! Please try again', 406, $e->getMessage());
        }

        return $this->success('Sponsor updated successfully', 200, [
            'sponsor' => $sponsor
        ]);
    }

     /**
     * Mark as published one or many sponsors
     *
     * @bodyParam ids string[] required An array list of ids associated with sponsors. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsPublished(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsPublishedValidationRules('sponsors'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();

            $this->sponsorDataService->markAsPublished($request->ids);

            DB::commit();

            return $this->success('Successfully marked as published the sponsor(s)!', 200);
        } catch (\Exception $e) {
            DB::rollback();

            return $this->error('Unable to mark as published the sponsor(s)! Please try again.', 406, $e->getMessage());
        }
    }

    /**
     * Mark as draft one or many sponsors
     *
     * @bodyParam ids string[] required An array list of ids associated with sponsors. Example: [1,2]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsDraft(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsDraftValidationRules('sponsors'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();

            $this->sponsorDataService->markAsDraft($request->ids);

            DB::commit();

            return $this->success('Successfully marked as draft the sponsor(s)!', 200);
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to mark as draft the sponsor(s)! Please try again.', 406, $e->getMessage());
        }
    }

    /**
     * Delete one or many sponsors
     *
     * @bodyParam ids string[] required An array list of ids associated with sponsors. Example: [1,2]
     *
     * @param SponsorsDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(SponsorsDeleteRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $this->sponsorDataService->destroy($request->validated('ids'));

            DB::commit();

        } catch (QueryException | \Exception $e) {
            DB::rollback();

            return $this->error('Unable to delete the sponsor! Please try again', 406, $e->getMessage());
        }

        return $this->success('Successfully deleted the sponsor(s)', 200);
    }

    /**
     * Restore One or many sponsors
     *
     * @bodyParam ids string[] required An array list of ids associated with sponsors. Example: [1,2]
     *
     * @param  mixed $request
     * @return JsonResponse
     */
    public function restore(SponsorsRestoreRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $this->sponsorDataService->restore($request->validated('ids'));

            DB::commit();

        } catch (QueryException | \Exception $e) {
            DB::rollback();

            return $this->error('Unable to restore the sponsor! Please try again', 406, $e->getMessage());
        }

        return $this->success('Successfully restored the sponsor(s)', 200);
    }

    /**
     * Delete one or many sponsors permanently
     * Only Administrator can delete a sponsor permanently.
     *
     * @bodyParam ids string[] required An array list of ids associated with sponsors. Example: [1,2]
     *
     * @param SponsorsDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(SponsorsDeleteRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $sponsors = $this->sponsorDataService->destroyPermanently($request->validated('ids'));

            DB::commit();

        } catch (QueryException | \Exception $e) {
            DB::rollback();

            return $this->error('Unable to delete the sponsor! Please try again', 406, $e->getMessage());
        }

        return $this->success('Sponsor deleted successfully', 200);
    }

    /**
     * Export Sponsors
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,created_at:desc
     *
     * @param SponsorListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function export(SponsorListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->sponsorDataService->setRelations([])->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while trying to export sponsors.', 400, $exception->getMessage());
        }
    }
}
