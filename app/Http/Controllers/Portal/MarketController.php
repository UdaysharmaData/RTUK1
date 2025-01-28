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

use App\Modules\Charity\Requests\ToggleResaleNotificationRequest;

use App\Modules\Event\Resources\EventResource;
use App\Modules\Charity\Resources\CharityResource;
use App\Modules\Charity\Resources\ResalePlaceResource;

use App\Enums\CharityUserTypeEnum;
use App\Modules\Charity\Models\ResaleNotification;
use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;

// use App\Exports\EventCsvExport;

/**
 * @group Market
 * Manages events market on the application
 * @authenticated
 */
class MarketController extends Controller
{
    use Response, SiteTrait, UploadTrait;

    /*
    |--------------------------------------------------------------------------
    | Market Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with events market. That is
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

        $this->middleware('role:can_manage_market', [
            'except' => [
                'index'
            ]
        ]);
    }

    /**
     * The list of resale places
     * 
     * Only the resales places that have not been all sold will be returned. This is made available to users of all roles.
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
                    $query->where('id', Auth::user()->id)
                        ->where(function($query) {
                            $query->where('type', CharityUserTypeEnum::Owner)
                                ->orWhere('type', CharityUserTypeEnum::User);
                        });
                });
            }
        }]);

        $resalePlaces = $resalePlaces->whereColumn('places', '>=', 'taken')
            ->whereHas('event.eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                });
        });

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
            'resale_places' => new ResalePlaceResource($resalePlaces)
        ]);
    }

    /**
     * The notifications
     * 
     * @queryParam event string Filter by event slug. Example: santa-in-the-city-london-wednesday
     * @queryParam charity string Filter by charity slug. Example: wwf
     * @queryParam term string Filter by term (event name). No-example
     * @queryParam status bool Filter by notification status. Example: 1
     * @queryParam page integer The page data to return. Example: 1
     * @queryParam per_page integer Items per page. No-example
     * 
     * @param  Request $request
     * @return JsonResponse
     */
    public function notifications(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'term' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'boolean'],
            'charity' => ['sometimes', 'nullable', Rule::exists('charities', 'slug')],
            'event' => ['sometimes', 'nullable', Rule::exists('events', 'slug')],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $events = Event::with(['eventCategories', 'resaleNotifications' => function ($query) {
                $query->with('charity');

                if (AccountType::isCharityOwnerOrCharityUser()) {
                    $query->whereHas('charity.users', function ($query) {
                        $query->where('user_id', Auth::user()->id);
                    });
                }
            }])->whereHas('eventCategories', function ($query) {
                    $query->whereHas('site', function ($query) {
                        $query->makingRequest();
                    });
                });

            if ($request->filled('event')) {
                $events = $events->where('slug', $request->event);
            }

            if ($request->filled('term') && $request->isNotFilled('event')) {
                $events = $events->where('name', 'like', '%'.$request->term.'%');
            }

            if ($request->filled('status')) {
                $events = $events->whereHas('resaleNotifications', function ($query) use ($request) {
                    $query->where('status', $request->status);

                    if (AccountType::isCharityOwnerOrCharityUser()) {
                        $query->whereHas('charity.users', function ($query) {
                            $query->where('user_id', Auth::user()->id);
                        });
                    }

                    if ($request->filled('charity')) { // Only get the events for which the resale notification has been set (to true or false) when filtering through charities
                        $query->whereHas('charity', function ($query) use ($request) {
                            $query->where('slug', $request->charity);
                        });
                    }
                });
            }

            $events = $events->orderBy(
                EventEventCategory::select('start_date')
                    ->whereColumn('event_id', 'events.id')
                    ->orderBy('start_date')
                    ->limit(1)
                );

            $perPage = $request->filled('per_page') ? $request->per_page : 10;
            $events = $events->paginate($perPage);

        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found', 406);
        }

        return $this->success('The notifications', 200, [
            'events_data' => new EventResource($events)
        ]);
    }

    /**
     * Toggle resale notifications
     * 
     * Turn ON/OFF notifications for the selected events on the authenticated charity profile
     * 
     * @param  ToggleResaleNotificationRequest $request
     * @return JsonResponse
     */
    public function toggleNotifications(ToggleResaleNotificationRequest $request): JsonResponse
    {
        if (! AccountType::isCharityOwnerOrCharityUser()) {
            return $this->error('You do not have permission to access this resource!', 403);
        }

        try {
            $action = $request->status ? 'turn on' : 'turn off';
            $action2 = $request->status ? 'turned on' : 'turned off';

            foreach ($request->events as $event) {
                ResaleNotification::updateOrCreate(
                    [
                        'event_id' => Event::firstWhere('slug', $event)->id,
                        'charity_id' => Auth::user()->charityUser?->charity_id // TODO: Review this given that charity users can belong to many charities
                    ],
                    [
                        'status' => $request->status
                    ]
                );
            }

        } catch (QueryException $e) {
            return $this->error('Unable to '. $action .' notification(s)! Please try again.', 406, $e->getMessage());
        }

        return $this->success('Successfully  '. $action2 .' notification(s)', 200);
    }
}
