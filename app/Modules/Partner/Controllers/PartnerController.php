<?php

namespace App\Modules\Partner\Controllers;

use App\Facades\ClientOptions;
use App\Http\Helpers\AccountType;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\Modules\Setting\Models\Site;
use App\Modules\Partner\Models\Partner;

use App\Modules\Partner\Resources\PartnerResource;

use App\Modules\Partner\Requests\PartnerCreateRequest;
use App\Modules\Partner\Requests\PartnerUpdateRequest;
use App\Modules\Partner\Requests\PartnerDeleteRequest;
use App\Modules\Partner\Requests\PartnerRestoreRequest;
use App\Modules\Partner\Requests\PartnerAllQueryParamsRequest;
use App\Modules\Partner\Requests\PartnerListingQueryParamsRequest;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\HelperTrait;
use App\Traits\SingularOrPluralTrait;

use App\Enums\ListTypeEnum;
use App\Enums\MetaRobotsEnum;
use App\Enums\SocialPlatformEnum;
use App\Enums\OrderByDirectionEnum;
use App\Enums\PartnersListOrderByFieldsEnum;

use App\Filters\PartnersOrderByFilter;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DefaultQueryParamService;
use App\Services\DataServices\PartnerDataService;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\FileManager\Traits\UploadModelTrait;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Partners
 * Manages partners on the application
 * @authenticated
 */
class PartnerController extends Controller
{
    use Response, SiteTrait, HelperTrait, SingularOrPluralTrait, UploadModelTrait;

    /*
    |--------------------------------------------------------------------------
    | Partner Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with partner. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected PartnerDataService $partnerDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_partners', [
            'except' => [
                'index'
            ]
        ]);
    }

    /**
     * Paginated partners for dropdown fields.
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam with string Get partners with channels. Must be one of channels. Example: channels
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param PartnerAllQueryParamsRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function all(PartnerAllQueryParamsRequest $request): JsonResponse
    {
        $partners = (new CacheDataManager(
            $this->partnerDataService,
            'all',
            [$request]
        ))->getData();

        return $this->success('All partners', 200, [
            'partners' => new PartnerResource($partners)
        ]);
    }

    /**
     * The list of partners
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,code:asc,expiry:desc,created_at:desc
     * @queryParam site string Filter by site ref. The site to search in. This parameter is only available to users of role administrator when making requests from sportsmediaagency.com (That is, when making request to get data of the whole application - all the platforms). No-example
     *
     * @param PartnerListingQueryParamsRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(PartnerListingQueryParamsRequest $request): JsonResponse
    {
        $partners = (new CacheDataManager(
            $this->partnerDataService,
            'getPaginatedList',
            [$request]
        ))->getData();

        return $this->success('The list of partners', 200, [
            'partners' => new PartnerResource($partners),
            'options' => ClientOptions::only('partners', [
                'deleted',
                'order_by',
                'order_direction',
            ]),
            'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Partners))
                ->setParams(['order_by' => PartnersListOrderByFieldsEnum::Name->value . ":" . OrderByDirectionEnum::Ascending->value])
                ->getDefaultQueryParams(),
            'action_messages' => Partner::$actionMessages
        ]);
    }

    /**
     * Create a partner
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return $this->success('Create a partner', 200, [
            'socials' => SocialPlatformEnum::_options(),
            'robots' => MetaRobotsEnum::_options()
        ]);
    }

    /**
     * Store a partner
     *
     * @param  PartnerCreateRequest  $request
     * @return JsonResponse
     */
    public function store(PartnerCreateRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $site = Site::where('ref', $request->site)->first();

            $partner = new Partner();
            $partner->fill($request->all());
            $partner->site()->associate($site);
            $partner->save();

            if ($request->filled('image')) { // Save the partner's logo
                $this->attachSingleUploadToModel($partner, $request->image);
            }

            $this->saveMetaData($request, $partner); // Save meta data

            if ($request->filled('socials') && $request->socials && $request->socials[0]['platform']) { // Update the partner's socials
                $this->saveSocials($request, $partner);
            }

            DB::commit();
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to create the partner! Please try again', 406, $e->getMessage());
        } catch (FileException $e) {
            DB::rollback();

            return $this->error('Unable to create the partner! Please try again', 406, $e->getMessage());
        }

        return $this->success('Successfully created the partner!', 200, new PartnerResource($partner->load(['meta', 'socials', 'upload'])));
    }

    /**
     * Edit a partner
     *
     * @urlParam partner_ref string required The ref of the partner. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string $ref
     * @return JsonResponse
     */
    public function edit(string $ref): JsonResponse
    {
        try {
            $_partner = (new CacheDataManager(
                $this->partnerDataService,
                'edit',
                [$ref]
            ))->getData();
        } catch (ModelNotFoundException $e) {
            return $this->error('The partner was not found!', 404);
        } catch (\Exception $e) {
            Log::error($e);

            return $this->error('An error occurred while fetching the partner!', 400);
        }

        return $this->success('Edit the partner', 200, [
            'partner' => new PartnerResource($_partner),
            'socials' => SocialPlatformEnum::_options(),
            'robots' => MetaRobotsEnum::_options(),
            'action_messages' => Partner::$actionMessages
        ]);
    }

    /**
     * Update a partner
     *
     * @urlParam partner_ref string required The ref of the partner. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  PartnerUpdateRequest  $request
     * @param  Partner $partner
     * @return JsonResponse
     */
    public function update(PartnerUpdateRequest $request, Partner $partner): JsonResponse
    {
        try {
            $_partner = Partner::withCount('partnerChannels')->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                })->where('ref', $partner->ref)
                ->firstOrFail();

            try {
                DB::beginTransaction();

                if ($request->filled('site')) {
                    $_partner->site_id = Site::where('ref', $request->site)->first()->id;
                }

                $_partner->fill($request->all());
                $_partner->save();

                if ($request->filled('image')) { // Update the partner's logo
                    $this->attachSingleUploadToModel($_partner, $request->image);
                }

                if ($request->filled('socials') && $request->socials && $request->socials[0]['platform']) { // Update the partner's socials
                    $this->saveSocials($request, $_partner);
                }

                $this->saveMetaData($request, $_partner); // Save meta data

                DB::commit();

                CacheDataManager::flushAllCachedServiceListings($this->partnerDataService);
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to update the partner! Please try again.', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The partner was not found!', 404);
        }

        return $this->success('Successfully updated the partner!', 200, new PartnerResource($_partner->load(['meta', 'socials', 'site', 'upload'])));
    }

    /**
     * Get a partner's details.
     *
     * @urlParam partner_ref string required The ref of the partner. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param string $ref
     * @return JsonResponse
     * @throws \Exception
     */
    public function show(string $ref): JsonResponse
    {
        try {
            $_partner = (new CacheDataManager(
                $this->partnerDataService,
                'show',
                [$ref]
            ))->getData();
        } catch (ModelNotFoundException $e) {

            return $this->error('The partner was not found!', 404);
        }

        return $this->success('The partner details', 200, new PartnerResource($_partner));
    }

    /**
     * Export partners
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:desc,code:asc,expiry:desc,created_at:desc
     * @queryParam site string Filter by site ref. The site to search in. This parameter is only available to users of role administrator when making requests from sportsmediaagency.com (That is, when making request to get data of the whole application - all the platforms). No-example
     *
     * @param PartnerListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|StreamedResponse|array|StreamedResponse
     */
    public function export(PartnerListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            return $this->partnerDataService->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while exporting events\' data.', 400);
        }
    }

    /**
     * Delete one or many partners
     *
     * @param  PartnerDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(PartnerDeleteRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $partners = Partner::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->withCount('partnerChannels')
                ->whereIn('ref', $request->refs)
                ->get();

            foreach ($partners as $partner) {
                if (!AccountType::isDeveloper() && $partner->partner_channels_count > 0) {
                    $message = 'Partners having channels setup were not deleted as they can only be deleted by developers since some are used in the code to fetch data from external sources!';
                } else {
                    $partner->delete();
                }
            }

            DB::commit();
        } catch (QueryException $e) {
            DB::rollback();
            return $this->error('Unable to delete the partner! Please try again.', 406, $e->getMessage());
        }

        return $this->success($message ?? 'Successfully deleted the ' . static::singularOrPlural(['partner', 'partners'], $request->refs) . '!', 200);
    }

    /**
     * Restore one or many partners
     *
     * @param  PartnerRestoreRequest $request
     * @return JsonResponse
     */
    public function restore(PartnerRestoreRequest $request): JsonResponse
    {
        try {
            $partners = Partner::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->whereIn('ref', $request->refs)
            ->onlyTrashed()
            ->get();

            if (! $partners->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($partners as $partner) {
                    $partner->restore();
                }

                DB::commit();
            } catch(QueryException $e) {
                DB::rollback();
                return $this->error('Unable to restore the '. static::singularOrPlural(['partner', 'partners'], $request->refs) .'! Please try again.', 406, $e->getMessage());
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The '. static::singularOrPlural(['partner was', 'partners were'], $request->refs) .' not found!', 404);
        }

        return $this->success('Successfully restored the '. static::singularOrPlural(['partner', 'partners'], $request->refs). '!', 200, new PartnerResource($partners));
    }

    /**
     * Delete one or many partners (Permanently)
     *
     * @param  PartnerDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(PartnerDeleteRequest $request): JsonResponse
    {
        try {
            $partners = Partner::whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            })->withCount('partnerChannels')
            ->whereIn('ref', $request->refs)
            ->withTrashed()
            ->get();

            if (! $partners->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($partners as $partner) {
                    if (!AccountType::isDeveloper() && $partner->partner_channels_count > 0) {
                        $message = 'Partners having channels setup were not deleted as they can only be deleted by developers since some are used in the code to fetch data from external sources!';
                    } else {
                        $partner->forceDelete();
                    }
                }

                DB::commit();
            } catch(QueryException $e) {
                DB::rollback();
                return $this->error('Unable to delete the '. static::singularOrPlural(['partner', 'partners'], $request->refs) .' permanently!', 406, $e->getMessage());
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The '. static::singularOrPlural(['partner was', 'partners were'], $request->refs) .' not found!', 404);
        }

        return $this->success($message ?? 'Successfully deleted the '. static::singularOrPlural(['partner', 'partners'], $request->refs) .' permanently!', 200);
    }

    /**
     * Remove the partner's image
     *
     * @urlParam partner_ref string required The ref of the partner. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam upload_ref string required The ref of the upload. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  Partner        $partner
     * @param  string         $upload_ref
     * @return JsonResponse
     */
    public function removeImage(Partner $partner, string $upload_ref): JsonResponse
    {
        $_partner = Partner::whereHas('site', function ($query) {
            $query->makingRequest();
        });

        try {
            $_partner = $_partner->where('ref', $partner->ref)
                ->firstOrFail();

            try {
                $_upload = $_partner->upload()
                    ->where('ref', $upload_ref)
                    ->firstOrFail();

                try {
                    $this->detachUpload($_partner, $_upload->ref);
                } catch (QueryException $e) {

                    return $this->error('Unable to delete the image! Please try again', 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {
                return $this->error('The image was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The partner was not found!', 404);
        }

        $_partner->load(['upload']);

        return $this->success('Successfully deleted the image!', 200, new PartnerResource($_partner));
    }
}
