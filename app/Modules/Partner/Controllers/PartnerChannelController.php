<?php

namespace App\Modules\Partner\Controllers;

use DB;
use Rule;
use Validator;
use App\Enums\ListTypeEnum;
use App\Enums\OrderByDirectionEnum;
use App\Enums\PartnerChannelsListOrderByFieldsEnum;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\AccountType;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Services\DefaultQueryParamService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\Modules\Partner\Models\Partner;
use App\Modules\Partner\Models\PartnerChannel;
use App\Facades\ClientOptions;

use App\Modules\Partner\Resources\PartnerChannelResource;

use App\Modules\Partner\Requests\PartnerChannelCreateRequest;
use App\Modules\Partner\Requests\PartnerChannelUpdateRequest;
use App\Modules\Partner\Requests\PartnerChannelDeleteRequest;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\SingularOrPluralTrait;

use App\Modules\Partner\Requests\PartnerChannelListingQueryParamsRequest;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\PartnerChannelDataService;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * @group Partner Channel
 * Manages partner channels on the application
 * @authenticated
 */
class PartnerChannelController extends Controller
{
    use Response, SiteTrait, SingularOrPluralTrait;

    /*
    |--------------------------------------------------------------------------
    | Partner Channel Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with partner channels. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected PartnerChannelDataService $partnerChannelDataService)
    {
        parent::__construct();

        $this->middleware('role:can_manage_partner_channels', [
            'except' => [
                'index'
            ]
        ]);
    }

    /**
     * Paginated partners channels for dropdown fields.
     * 
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * 
     * @param  Request       $request
     * @return JsonResponse
     */
    public function all(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'term' => ['sometimes', 'nullable', 'string'],
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $channels = (new CacheDataManager(
            $this->partnerChannelDataService,
            'all',
            [$request]
        ))->getData();

        return $this->success('All partner channels', 200, [
            'partner_channels' => new PartnerChannelResource($channels)
        ]);
    }

    /**
     * The list of partner channels
     *
     * @queryParam partner string Filter by partner ref. No-example
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     * @queryParam site string Filter by site ref. The site to search in. This parameter is only available to users of role administrator when making requests from sportsmediaagency.com (That is, when making request to get data of the whole application - all the platforms). No-example
     *
     * @param  PartnerChannelListingQueryParamsRequest  $request
     * @return JsonResponse
     */
    public function index(PartnerChannelListingQueryParamsRequest $request): JsonResponse
    {
        $channels = (new CacheDataManager(
            $this->partnerChannelDataService,
            'getPaginatedList',
            [$request]
        ))->getData();

        return $this->success('The list of partner channels', 200, [
            'channels' => new PartnerChannelResource($channels),
            'options' => ClientOptions::only('partner_channels', [
                'order_by',
                'order_direction'
            ]),
            'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::PartnerChannels))
                ->setParams(['order_by' => PartnerChannelsListOrderByFieldsEnum::Name->value . ":" . OrderByDirectionEnum::Ascending->value])
                ->getDefaultQueryParams()
        ]);
    }

    /**
     * Create a partner channel
     * 
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return $this->success('Create a partner channel', 200);
    }

    /**
     * Store a partner channel
     * 
     * @param  PartnerChannelCreateRequest  $request
     * @return JsonResponse
     */
    public function store(PartnerChannelCreateRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $partnerChannel = new PartnerChannel();
            $partnerChannel->fill($request->all());
            $partnerChannel->partner()->associate(Partner::where('ref', $request->partner)->first());
            $partnerChannel->save();

            DB::commit();
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to create the partner channel! Please try again', 406, $e->getMessage());
        } catch (FileException $e) {
            DB::rollback();

            return $this->error('Unable to create the partner channel! Please try again', 406, $e->getMessage());
        }

        return $this->success('Successfully created the partner channel!', 200, new PartnerChannelResource($partnerChannel));
    }

    /**
     * Edit a partner channel
     *
     * @urlParam channel_ref string required The ref of the partner channel. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * 
     * @param  PartnerChannel  $partner
     * @return JsonResponse
     */
    public function edit($ref): JsonResponse
    {
        try {
            $channel = (new CacheDataManager(
                $this->partnerChannelDataService,
                'edit',
                [$ref]
            ))->getData();
        } catch (ModelNotFoundException $e) {
            return $this->error('The partner channel was not found!', 404);
        }

        return $this->success('Edit the partner channel', 200, [
            'partner' => new PartnerChannelResource($channel)
        ]);
    }

    /**
     * Update a partner channel
     *
     * @urlParam channel_ref string required The ref of the partner channel. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * 
     * @param  PartnerChannelUpdateRequest  $request
     * @param  PartnerChannel               $channel
     * @return JsonResponse
     */
    public function update(PartnerChannelUpdateRequest $request, PartnerChannel $channel): JsonResponse
    {
        if (!AccountType::isDeveloper()) { // Only the developer can update a partner channel since some of them are used in some commands to fetch data from external sources
            return $this->error('You do not have permission to access this resource. Only developers can update partner channels since some are used in the code to fetch data from external sources!', 403);
        }

        try {
            $_channel = PartnerChannel::whereHas('partner', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            })->where('ref', $channel->ref)
                ->firstOrFail();

            try {
                DB::beginTransaction();

                if ($request->filled('partner')) {
                    $request['partner_id'] = Partner::where('ref', $request->partner)->value('id');
                }

                $_channel->update($request->all());

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to update the partner channel! Please try again.', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {

            return $this->error('The partner channel was not found!', 404);
        }

        return $this->success('Successfully updated the partner channel!', 200, new PartnerChannelResource($_channel->load(['partner'])));
    }

    /**
     * Delete one or many partner channels
     * 
     * @param  PartnerChannelDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(PartnerChannelDeleteRequest $request): JsonResponse
    {
        if (!AccountType::isDeveloper()) { // Only the developer can delete partner channels.
            return $this->error('You do not have permission to access this resource. Only developers can delete partner channels since some are used in the code to fetch data from external sources!', 403);
        }

        try {
            PartnerChannel::whereHas('partner', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            })->whereIn('ref', $request->refs)->delete();

            CacheDataManager::flushAllCachedServiceListings($this->partnerChannelDataService);
            
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error('Unable to delete the partner channel! Please try again.', 406, $e->getMessage());
        }

        return $this->success('Successfully deleted the partner channel', 200);
    }
}