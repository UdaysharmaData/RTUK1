<?php

namespace App\Http\Controllers\Portal;

use DB;
use Str;
use Auth;
use Rule;
use Excel;
use Storage;
use Validator;
use Carbon\Carbon;
use App\Http\Helpers\Years;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\AccountType;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\Models\Upload;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Charity\Models\ResalePlace;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Charity\Models\ResaleNotification;

use App\Events\ResaleEventOnSaleEvent;

use App\Modules\Charity\Requests\ResalePlaceCreateRequest;

use App\Modules\Event\Resources\EventResource;
use App\Modules\Charity\Resources\CharityResource;
use App\Modules\Charity\Resources\ResalePlaceResource;

use App\Enums\CharityUserTypeEnum;
use App\Enums\ResaleRequestStateEnum;
use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;

// use App\Exports\EventCsvExport;

/**
 * @group ResalePlace
 * Manages events resale places on the application
 * @authenticated
 */
class ResalePlaceController extends Controller
{
    use Response, SiteTrait, UploadTrait;

    /*
    |--------------------------------------------------------------------------
    | Resale Place Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with events resale places. That is
    | the creation, view, update, delete and more ...
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('role:can_manage_market');
    }

    /**
     * The list of resale places
     * 
     * @queryParam charity string Filter by charity slug. Example: wwf
     * @queryParam event string Filter by event slug. Example: santa-in-the-city-london-wednesday
     * @queryParam discount bool Filter by status. Example: 1
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return. Example: 1
     * @queryParam per_page integer Items per page. No-example
     * 
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'term' => ['sometimes', 'nullable', 'string'],
            'discount' => ['sometimes', 'nullable', 'boolean'],
            'charity' => ['sometimes', 'nullable', Rule::exists('charities', 'slug')],
            'event' => ['sometimes', 'nullable', Rule::exists('events', 'slug')],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $resalePlaces = ResalePlace::with(['charity', 'event.eventCategories', 'resaleRequests' => function ($query) {
            if (AccountType::isCharityOwnerOrCharityUser()) {
                $query->whereHas('charity.users', function ($query) {
                    $query->where('user_id', Auth::user()->id)
                        ->where(function($query) {
                            $query->where('type', CharityUserTypeEnum::Owner)
                                ->orWhere('type', CharityUserTypeEnum::User);
                        });
                });
            }
        }]);

        $resalePlaces = $resalePlaces->whereHas('event.eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess();
                });
        });

        if (AccountType::isCharityOwnerOrCharityUser()) {
            $resalePlaces = $resalePlaces->whereHas('charity.users', function ($query) {
                $query->where('user_id', Auth::user()->id);
            });
        }

        if ($request->filled('charity')) {
            $resalePlaces = $resalePlaces->whereHas('charity', function ($query) use ($request) {
                $query->where('slug', $request->charity);
            });
        }

        if ($request->filled('event')) {
            $resalePlaces = $resalePlaces->whereHas('event', function ($query) use ($request) {
                $query->where('slug', $request->event);
            });
        }

        if ($request->filled('term') && $request->isNotFilled('event')) {
            $resalePlaces = $resalePlaces->whereHas('event', function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->term.'%');
            });
        }

        if ($request->filled('discount')) {
            if ($request->discount) {
                $resalePlaces = $resalePlaces->whereNotNull('discount');
            } else {
                $resalePlaces = $resalePlaces->whereNull('discount');
            }
        }

        $resalePlaces = $resalePlaces->orderByDesc('created_at');

        $perPage = $request->filled('per_page') ? $request->per_page : 10;
        $resalePlaces = $resalePlaces->paginate($perPage);

        return $this->success('The list of resale places', 200, [
            'resale_places' => new ResalePlaceResource($resalePlaces),
        ]);
    }

    /**
     * Create a resale place
     * 
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        return $this->success('Create a resale place!', 200);
    }

    /**
     * Store a resale place
     * 
     * @param ResalePlaceCreateRequest $request
     * @return JsonResponse
     */
    public function store(ResalePlaceCreateRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $resalePlace = ResalePlace::firstOrCreate(
                [
                    'charity_id' => Charity::firstWhere('slug', $request->charity)?->id,
                    'event_id' => Event::firstWhere('slug', $request->event)?->id
                ],
                [
                    'places' => $request->places,
                    'unit_price' => $request->unit_price
                ]
            );

            event(new ResaleEventOnSaleEvent($resalePlace, $request->places));  // Notify interested charities that the event's places have gone on sale on the market place

            DB::commit();
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to create the resale place! Please try again', 406, $e->getMessage());
        } catch (FileException $e) {
            DB::rollback();

            return $this->error('Unable to create the resale place! Please try again', 406, $e->getMessage());
        }

        return $this->success('Successfully added the resale places to the market place!', 200, new ResalePlaceResource($resalePlace));
    }

    /**
     * Edit a resale place
     *
     * @urlParam id int required The id of the resale place. Example: 1
     * @return JsonResponse
     */
    public function edit(int $id): JsonResponse
    {
        try {
            $resalePlace = ResalePlace::query();

            $resalePlace = $resalePlace->whereHas('event.eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            });

            if (AccountType::isCharityOwnerOrCharityUser()) {
                $resalePlace = $resalePlace->whereHas('charity.users', function ($query) {
                    $query->where('user_id', Auth::user()?->id);
                });
            }

            $resalePlace = $resalePlace->findOrFail($id);

        } catch (ModelNotFoundException $e) {
            return $this->error('The resale place was not found!', 404);
        }

        return $this->success('Edit the resale place', 200, [
            'resale_place' => new ResalePlaceResource($resalePlace)
        ]);
    }

    /**
     * Update a resale place
     *
     * @param  ResalePlaceCreateRequest  $request
     * @urlParam id int required The id of the resale place. Example: 1
     * @return JsonResponse
     */
    public function update(ResalePlaceCreateRequest $request, int $id): JsonResponse
    { 
        try {
            $resalePlace = ResalePlace::whereHas('event.eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            });

            if (AccountType::isCharityOwnerOrCharityUser()) {
                $resalePlace = $resalePlace->whereHas('charity.users', function ($query) {
                    $query->where('user_id', Auth::user()?->id);
                });
            }

            $resalePlace = $resalePlace->findOrFail($id);

            try {

                $resalePlace->fill($request->all());

                $resalePlace->save();

            } catch (QueryException $e) {
                return $this->error('Unable to update the resale place!', 406, $e->getMessage());
            }

        } catch (ModelNotFoundException $e) {
            return $this->error('The resale place was not found!', 404);
        }

        return $this->success('Successfully updated the event!', 200, new ResalePlaceResource($resalePlace));
    }

    /**
     * Delete a resale place
     *
     * @urlParam id int required The id of the resale place. Example: 1
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    { 
        try {
            $resalePlace = ResalePlace::whereHas('event.eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            });

            if (AccountType::isCharityOwnerOrCharityUser()) {
                $resalePlace = $resalePlace->whereHas('charity.users', function ($query) {
                    $query->where('user_id', Auth::user()?->id);
                });
            }

            $resalePlace = $resalePlace->findOrFail($id);

            try {
                $resalePlace = $resalePlace->resaleRequests()->whereNot('state', ResaleRequestStateEnum::Paid)->firstOrFail();

                try {
                    $resalePlace->delete();

                } catch (QueryException $e) {
                    return $this->error('Unable to update the resale place!', 406, $e->getMessage());
                }
            } catch (QueryException $e) {
                return $this->error('The market place item has a Paid request. Consequently, it cannot be deleted!', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The resale place was not found!', 404);
        }

        return $this->success('Successfully deleted the event!', 200, new ResalePlaceResource($resalePlace));
    }

}
