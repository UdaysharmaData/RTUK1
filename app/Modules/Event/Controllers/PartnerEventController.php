<?php

namespace App\Modules\Event\Controllers;

use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Str;
use Auth;
use Rule;
use Excel;
use Storage;
use Validator;
use Carbon\Carbon;
use App\Http\Helpers\Years;
use Illuminate\Http\Request;
use App\Enums\EventStateEnum;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\AccountType;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\DataServices\PartnerEventDataService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

use App\Models\Invoice;
use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;

use App\Modules\Event\Resources\EventResource;

use App\Modules\Participant\Requests\ParticipantCreateRequest;

use App\Enums\MonthEnum;
use App\Enums\FeeTypeEnum;
use App\Enums\RoleNameEnum;
use App\Enums\BoolYesNoEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\EventCharitiesEnum;
use App\Enums\CharityUserTypeEnum;
use App\Enums\InvoiceItemTypeEnum;
use App\Enums\ParticipantWaiveEnum;
use App\Enums\ParticipantStatusEnum;
use App\Enums\ParticipantWaiverEnum;
use App\Enums\ParticipantAddedViaEnum;
use App\Enums\ParticipantActionTypeEnum;
use App\Enums\EventCategoryVisibilityEnum;
use App\Enums\ParticipantPaymentStatusEnum;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;
use App\Traits\DownloadTrait;

use App\Services\DataCaching\CacheDataManager;

use App\Facades\ClientOptions;
use App\Exports\PartnerEventCsvExport;
use App\Modules\Event\Requests\PartnerEventParticipantsListingQueryParamsRequest;
use App\Modules\Participant\Resources\ParticipantResource;

use App\Events\ParticipantNewRegistrationsEvent;

/**
 * @group Partner Events
 * Manages partner events on the application
 * @authenticated
 */
class PartnerEventController extends Controller
{
    use Response,
        SiteTrait,
        UploadTrait,
        DownloadTrait;

    /*
    |--------------------------------------------------------------------------
    | Partner Event Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with partner events.
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected PartnerEventDataService $partnerEventDataService)
    {
        parent::__construct();

        $this->middleware('role:can_offer_place_to_events', [
            'except' => 'index'
        ]);
    }

    /**
     * The list of partner events
     *
     * @queryParam category string required The ref of the category. Example: 97a3ca24-0447-4b49-aa25-a8cddb0e064d
     * @queryParam year int Filter by year. No-example
     * @queryParam month int Filter by month. No-example
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'term' => ['sometimes', 'nullable', 'string'],
            'year' => ['sometimes', 'nullable', 'digits:4', 'date_format:Y'],
            'month' => ['sometimes', 'nullable', 'numeric', 'min:1', 'max:12'],
            'category' => ['sometimes', 'nullable', Rule::exists('event_categories', 'ref')->where(
                function ($query) {
                    return $query->where("site_id", static::getSite()?->id);
                })],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $events = (new CacheDataManager(
            $this->partnerEventDataService,
            'getPaginatedList',
            [$request]
        ))->getData();

        return $this->success('The list of partner events', 200, [
            'events' => new EventResource($events),
            'options' => [...ClientOptions::only('partner_events', [
                'archived',
                'months',
                'years'
            ])]
        ]);
    }

    /**
     * Create a participant
     *
     * @urlParam event_ref string required The ref of the event. Example: 97a3ca24-0447-4b49-aa25-a8cddb0e064d
     *
     * @param  string        $event
     * @return JsonResponse
     */
    public function createParticipant(string $event): JsonResponse
    {
        try {
            $_event = Event::with(['eventCategories' => function ($query) {
                $query->orderByDesc('registration_deadline');
            }, 'image'])
            ->withCount(['participants as registrations' => function ($query) {
                $query->filterByAccess();
            }, 'participants as complete_registrations' => function ($query) {
                $query->filterByAccess()
                    ->completedRegistration();
            }])
            ->partnerEvent(Event::ACTIVE)
            ->estimated(Event::INACTIVE)
            ->state(EventStateEnum::Live)
            ->where('status', Event::ACTIVE)
            ->whereHas('eventCategories', function($query) {
                $query->whereHas('site', function($query) {
                    $query->makingRequest();
                })->where(function ($query) {
                    $query->whereNull('registration_deadline')
                        ->orWhere('registration_deadline', '>=', Carbon::now());
                })->where('end_date', '>=', Carbon::now());
            })->when(
                AccountType::isParticipant(),
                fn ($query) => $query->where('exclude_participants', Event::INACTIVE) // Don't return events that exclude participants
            );

            $_event = $_event->where('ref', $event)->firstOrFail();

            $_event['registrations_summary'] = Event::registrationsSummary($_event);

            try {
                $regActive = $_event->eventCategories[0]->pivot->registrationActive();

                if (! $regActive->status) { // Check if registrations are still active
                    throw new \Exception($regActive->message);
                }
            } catch (\Exception $e) {
                $message = $e->getMessage();
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success($message ?? 'Create a participant', 200, [
            'event_status' => isset($message) ? false : true,
            'event' => new EventResource($_event),
            'payment_statuses' => ParticipantPaymentStatusEnum::_options([ParticipantPaymentStatusEnum::Refunded, ParticipantPaymentStatusEnum::Transferred]),
            'waives' => ParticipantWaiveEnum::_options(),
            'waivers' => ParticipantWaiverEnum::_options(),
            'fee_types' => Event::feeTypeOptions($_event)
        ]);
    }

    /**
     * Store a participant.
     * The administrator or charity owners or charity users add participants to events through this endpoint.
     *
     * @urlParam event_ref string required The ref of the event. Example: 97a3ca24-0447-4b49-aa25-a8cddb0e064d
     *
     * @param  ParticipantCreateRequest  $request
     * @param  string                    $event
     * @return JsonResponse
     */
    public function storeParticipant(ParticipantCreateRequest $request, string $event): JsonResponse
    {
        try {
            $_event = Event::with(['eventCategories' => function ($query) {
                    $query->orderByDesc('registration_deadline');
                }, 'image'])
                ->partnerEvent(Event::ACTIVE)
                ->whereHas('eventCategories', function($query) {
                    $query->whereHas('site', function($query) {
                        $query->makingRequest();
                    });
                });

            $_event = $_event->where('ref', $event)->firstOrFail();

            try {
                $eec = EventEventCategory::with(['event', 'eventCategory'])
                    ->where('ref', $request->eec)
                    ->whereHas('eventCategory', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->makingRequest();
                        });
                    })->firstOrFail();

                try {
                    DB::beginTransaction();

                    $register = Participant::registerForEvent($request, $eec, ParticipantAddedViaEnum::PartnerEvents);

                    DB::commit();

                    $passed = [
                        'eecs' => [
                            [
                                'id' => $eec->id,
                                'ref' => $eec->ref,
                                'name' => $eec->event->formattedName,
                                'category' => $eec->eventCategory?->name,
                                'reg_status' => $register->status,
                                'registration_fee' => $eec->userRegistrationFee($register->user),
                                'participant' => $register->participant
                            ]
                        ]
                    ];

                    $extraData = [
                        'passed' => $passed,
                        'wasRecentlyCreated' => $register->wasRecentlyCreated
                    ];

                    event(new ParticipantNewRegistrationsEvent($register->user, $extraData, $register->participant->invoiceItem?->invoice, clientSite())); // Notify participant via email

                    // Notify participant via email
                } catch (QueryException $e) {
                    DB::rollback();
                    \Log::debug($e);
                    // TODO: LOG a message to notify the developer's on slack
                    return $this->error('Unable to create the participant! Please try again.', 406, $e->getMessage());
                } catch (\Exception $e) {
                    DB::rollback();
                    \Log::debug($e);
                    // TODO: LOG a message to notify the developer's on slack
                    return $this->error($e->getMessage(), 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {
                \Log::debug($e);
                return $this->error('The event category does not belong to the event!', 404, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            \Log::debug($e);
            return $this->error('The event was not found!', 404);
        }

        return $this->success("Successfully created the participant" . ($register->_message ?? null) . "!", 200, new ParticipantResource($register->participant->load(['charity:id,ref,name,slug', 'eventEventCategory.event:id,ref,name,slug', 'eventEventCategory.eventCategory:id,ref,name,slug', 'user:id,ref,email,first_name,last_name'])));
    }

    /**
     * Get the participants for an event
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam page integer No-example
     * @queryParam per_page integer Items per page No-example
     * @queryParam site string Filter by site ref. The site to search in. This parameter is only available to users of role administrator when making requests from sportsmediaagency.com (That is, when making request to get data of the whole application - all the platforms). No-example
     *
     * @param PartnerEventParticipantsListingQueryParamsRequest $request
     * @param string $event
     * @return JsonResponse
     * @throws \Exception
     */
    public function participants(PartnerEventParticipantsListingQueryParamsRequest $request, string $event): JsonResponse
    {
        $request['event'] = $event;

        $participants = (new CacheDataManager(
            $this->partnerEventDataService,
            'participants',
            [$request]
        ))->getData();

        return $this->success('The participants for the event!', 200, [
            'participants' => new ParticipantResource($participants)
        ]);
    }

    /**
     * Export event participants
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam page integer No-example
     * @queryParam per_page integer Items per page No-example
     * @queryParam site string Filter by site ref. The site to search in. This parameter is only available to users of role administrator when making requests from sportsmediaagency.com (That is, when making request to get data of the whole application - all the platforms). No-example
     *
     * @param PartnerEventParticipantsListingQueryParamsRequest $request
     * @param string $event
     * @return BinaryFileResponse|JsonResponse|array|StreamedResponse
     */
    public function exportEventParticipants(PartnerEventParticipantsListingQueryParamsRequest $request, string $event): BinaryFileResponse|JsonResponse|array|StreamedResponse
    {
        try {
            $request['event'] = $event;

            return $this->partnerEventDataService->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while exporting events\' data.', 400);
        }
    }
}
