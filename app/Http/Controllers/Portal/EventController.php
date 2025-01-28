<?php

namespace App\Http\Controllers\Portal;

use App\Models\EventRegionLinking;
use App\Models\EventCityLinking;
use App\Models\EventVenuesLinking;
use DB;
use Str;
use Auth;
use Rule;
use Excel;
use Schema;
use Storage;
use Exception;
use Validator;
use Carbon\Carbon;
use App\Http\Helpers\Years;
use Illuminate\Http\Request;
use App\Facades\ClientOptions;
use App\Http\Helpers\TextHelper;
use Illuminate\Http\JsonResponse;
use App\Http\Helpers\AccountType;
use App\Events\EventArchivedEvent;
use App\Http\Helpers\FormatNumber;
use App\Events\EventsArchivedEvent;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Database\QueryException;
use App\Jobs\AddEventToPromotionalPagesJob;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use App\Modules\Event\Requests\EventCreateRequest;
use App\Modules\Event\Requests\EventUpdateRequest;
use App\Modules\Event\Requests\EventDeleteRequest;
use App\Modules\Event\Requests\EventRegFieldsUpdateRequest;
use App\Modules\Event\Requests\EventThirdPartyCreateRequest;
use App\Modules\Event\Requests\EventThirdPartyUpdateRequest;
use App\Modules\Event\Requests\EventCustomFieldDeleteRequest;
use App\Modules\Event\Requests\EventThirdPartyDeleteRequest;
use App\Modules\Event\Requests\EventCustomFieldCreateRequest;
use App\Modules\Event\Requests\EventCustomFieldUpdateRequest;
use App\Modules\Event\Requests\EventListingQueryParamsRequest;

use App\Models\Faq;
use App\Models\City;
use App\Models\Medal;
use App\Models\Venue;
use App\Models\Region;
use App\Models\FaqDetails;
use App\Modules\User\Models\User;
use App\Modules\Event\Models\Event;
use App\Modules\Event\Models\Serie;
use App\Modules\Event\Models\Sponsor;
use App\Modules\Charity\Models\Charity;
use App\Modules\Partner\Models\Partner;
use App\Modules\Event\Models\EventManager;
use App\Modules\Event\Models\EventCategory;
use App\Modules\Event\Models\EventThirdParty;
use App\Modules\Partner\Models\PartnerChannel;
use App\Modules\Event\Models\EventCustomField;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Event\Models\TotalPlacesNotification;

use App\Modules\Event\Resources\EventResource;
use App\Modules\Event\Resources\EventThirdPartyResource;
use App\Modules\Event\Resources\EventCustomFieldResource;
use App\Modules\Participant\Resources\ParticipantResource;

use App\Repositories\FaqRepository;

use App\Enums\RoleNameEnum;
use App\Enums\ListTypeEnum;
use App\Enums\BoolYesNoEnum;
use App\Enums\EventTypeEnum;
use App\Enums\EventStateEnum;
use App\Enums\MetaRobotsEnum;
use App\Enums\EventReminderEnum;
use App\Enums\LocationUseAsEnum;
use App\Enums\EventCharitiesEnum;
use App\Enums\SocialPlatformEnum;
use App\Enums\CharityUserTypeEnum;
use App\Enums\CharityEventTypeEnum;
use App\Enums\OrderByDirectionEnum;
use App\Enums\BoolActiveInactiveEnum;
use App\Enums\EventRouteInfoTypeEnum;
use App\Enums\EventCustomFieldTypeEnum;
use App\Enums\EventCustomFieldRuleEnum;
use App\Enums\EventCategoryVisibilityEnum;
use App\Enums\EventsListOrderByFieldsEnum;
use App\Enums\UploadUseAsEnum;
use App\Http\Helpers\LocationHelper;
use App\Http\Resources\MedalResource;
use App\Http\Requests\DeleteEventFaqsRequest;
use App\Http\Requests\DeleteFaqDetailsRequest;
use App\Modules\Event\Requests\EventRestoreRequest;
use App\Modules\Event\Requests\EventAllQueryParamsRequest;


use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\HelperTrait;
use App\Traits\DownloadTrait;
use App\Traits\SingularOrPluralTrait;
use App\Traits\OrderByParamValidationClosure;

use Illuminate\Support\Facades\Log;
use App\Services\DefaultQueryParamService;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EventClientDataService;
use App\Services\DataServices\EventDataService;
use App\Services\DataServices\GlobalSearchDataService;
use App\Services\DataServices\PartnerEventDataService;
use App\Services\DataServices\UserDataService;
use App\Services\ExportManager\Exceptions\ExportableDataMissingException;
use App\Services\FileManager\Traits\UploadModelTrait;
use App\Traits\DraftCustomValidator;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Support\Facades\Route;

/**
 * @group Events
 * Manages events on the application
 * @authenticated
 */
class EventController extends Controller
{
    use Response,
        SiteTrait,
        HelperTrait,
        DownloadTrait,
        SingularOrPluralTrait,
        OrderByParamValidationClosure,
        UploadModelTrait,
        DraftCustomValidator;

    /*
    |--------------------------------------------------------------------------
    | Event Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with events. That is
    | the creation, view, update, delete and more ...
    |
    */
    protected $eventService;

    public function __construct(protected FaqRepository $faqRepository)
    {
        $this->faqRepository = $faqRepository;
     
        $action = Route::currentRouteAction();
        $functionName = explode('@', $action)[1];
        $this->eventService = new EventDataService($functionName !== 'export');

        parent::__construct();

        $this->middleware('role:can_manage_events', [
            'except' => [
                'all',
                'allWithCategories',
                'toggleTotalPlacesNotifications',
                'getImage',
                'participants',
                'upcoming'
            ]
        ]);
    }

    /**
     * Paginated events for dropdown fields.
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam with object Fields to filter by.
     * @queryParam with.value string Get events with categories or with third parties. Must be one of categories, third_parties. Example: categories
     * @queryParam with.visibility string Filter the event categories of the events returned above. Must be one of public, private. Example: public
     * @queryParam active boolean Filter by active events. Example: true
     * @queryParam state string Filter by state. Must be one of live, expired, archived. Example: live
     * @queryParam extra_attributes string Filter by extra properties. Example: 'country,estimated,partner_event'
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example,
     *
     * @param  EventAllQueryParamsRequest  $request
     * @return JsonResponse
     */
    public function all(EventAllQueryParamsRequest $request): JsonResponse
    {
        $events = (new CacheDataManager(
            $this->eventService,
            'all',
            [$request]
        ))->getData();

        return $this->success('All events', 200, [
            'events' => new EventResource($events)
        ]);
    }

    /**
     * The list of events
     *
     * @queryParam state string Filter by state. Must be one of live, expired, archived. Example: live
     * @queryParam category string Filter by event category ref. No-example
     * @queryParam region string Filter by region ref. No-example
     * @queryParam city string Filter by city ref. No-example
     * @queryParam venue string Filter by venue ref. No-example
     * @queryParam event_experience string Filter by event_experience ref. No-example
     * @queryParam year int Filter by year. No-example
     * @queryParam month int Filter by month. No-example
     * @queryParam status boolean Filter by status. Example: true
     * @queryParam type string Filter by type. Should be one of rolling, standalone. Example: standalone
     * @queryParam partner_event boolean Filter by partner_event. Example: true
     * @queryParam country boolean Filter by country. Example: United Kingdom
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam faqs string Specifying the inclusion of ONLY pages with associated FAQs. Should be one of with, without. Example: with
     * @queryParam medals string Specifying the inclusion of ONLY pages with associated medals. Should be one of with, without. Example: with
     * @queryParam ids integer[] Filter by ids. Must be an array of ids. Example: [148, 153]
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam drafted string Specifying how to interact with drafted items. Example: with
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:asc,start_date:asc,end_date:desc
     * @queryParam year string Specifying year filter for when user was created. Example: 2023
     * @queryParam site string Filter by site ref. The site to search in. This parameter is only available to users of role administrator when making requests from sportsmediaagency.com (That is, when making request to get data of the whole application - all the platforms). No-example
     * @queryParam has_third_party_set_up boolean Filter by events having third party setup or not. Example: 1
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param EventListingQueryParamsRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(EventListingQueryParamsRequest $request): JsonResponse
    {
        $events = (new CacheDataManager(
            $this->eventService->setLoadRedirect(true),
            'getPaginatedList',
            [$request]
        ))->getData();

        return $this->success('The list of events', 200, [
            'events' => new EventResource($events),
            'options' => ClientOptions::only('events', [
                'types',
                'states',
                'months',
                'order_by',
                'deleted',
                'drafted',
                'order_direction',
                'faqs',
                'years',
                'medals',
                'partner_event',
                'has_third_party_set_up'
            ]),
            'default_query_params' => (new DefaultQueryParamService(ListTypeEnum::Events))->setParams([
                'order_by' => EventsListOrderByFieldsEnum::StartDate->value. ":" . OrderByDirectionEnum::Descending->value,
            ])->getDefaultQueryParams(),
            'action_messages' => Event::$actionMessages
        ]);
    }

    /**
     * Create an event
     *
     * @return JsonResponse
     */
    public function create(): JsonResponse
    {
        $users = User::select('id', 'ref', 'first_name', 'last_name')->whereHas('roles', function ($query) {
            $query->where('name', RoleNameEnum::EventManager)
                ->whereHas('site', function ($query) {
                    $query->makingRequest();
                });
        })->get();

        return $this->success('Create an event', 200, [
            'event_managers' => $users,
            'types' => EventTypeEnum::_options(),
            'partners' => BoolYesNoEnum::_options(),
            'socials' => SocialPlatformEnum::_options(),
            'reminders' => EventReminderEnum::_options(),
            'robots' => MetaRobotsEnum::_options(),
            'statuses' => BoolActiveInactiveEnum::_options(),
            'route_info_types' => EventRouteInfoTypeEnum::_options(),
            'event_withdrawal_weeks' => config('app.event_withdrawal_weeks')
        ]);
    }

    /**
     * Store an event
     *
     * @param EventCreateRequest $request
     * @return JsonResponse
     */
    public function store(EventCreateRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $event = new Event();
            $event->fill($request->all());

            // $event->region_id = Region::where('ref', $request->region)
            //     ->value('id');

            // if ($request->has('city')) {
            //     $event->city_id = City::where('ref', $request->city)
            //         ->value('id');
            // }

            // if ($request->has('venue')) {
            //     $event->venue_id = Venue::where('ref', $request->venue)
            //         ->value('id');
            // }

            if ($request->has('serie')) {
                $event->serie_id = Serie::where('ref', $request->serie)
                    ->value('id');
            }

            if ($request->has('sponsor')) {
                $event->sponsor_id = Sponsor::where('ref', $request->sponsor)
                    ->value('id');
            }

            $event->save();

            if ($request->filled('location')) { // Create the event address
                LocationHelper::saveAddress($event, $request);
            }

            if ($request->filled('event_managers')) { // Save the event managers
                $eventManagers = EventManager::whereHas('user', function ($query) use ($request) {
                    $query->whereIn('ref', $request->event_managers);
                })->pluck('id');

                $event->eventManagers()->sync($eventManagers);
            }

            if ($request->filled('categories')) {
                foreach ($request->categories as $category) { // Link the event to the event categories
                    $categoryId = EventCategory::where('ref', $category['ref'])?->value('id');

                    unset($category['ref']);

                    $event->eventCategories()->attach($categoryId, $category);
                }
            }

            if ($request->filled('third_parties')) { // Save third party configurations
                foreach ($request->third_parties as $thirdParty) {
                    $this->saveEventThirdParty($event, (array) $thirdParty);
                }
            }

            if ($request->filled('charities') && $request->charities != EventCharitiesEnum::All->value) { // Save included or excluded charities
                if ($request->filled('_charities') && $request->filled('charities')) {
                    $_charities = Charity::whereIn('ref', $request->_charities)->pluck('id');
                    $event->includedExcludedCharities()->syncWithPivotValues($_charities, ['type' => CharityEventTypeEnum::from($request->charities)]);
                }
            }

            if ($request->filled('image')) { // Save the event's image
                $this->attachSingleUploadToModel($event, $request->image);
            }

            if ($request->filled('gallery')) { // Save the event's gallery | TODO: Look deeper into these images upload and improve on it by adding a possibility to add an image(s) without deleting the existing ones (you may use a different route for this). Also use a route to delete an image.
                $this->attachMultipleUploadsToModel($event, $request->gallery, UploadUseAsEnum::Gallery);
            }

            if ($request->filled('route_info')) { // Save the Route Infomation Description & (Media or Embed Link)
                if ($request->has('route_info.description')) {
                    $event->route_info_description = $request->route_info['description'];
                }

                if ($request->filled('route_info.type') && $request->route_info['type'] == EventRouteInfoTypeEnum::EmbedCode->value && $request->has('route_info.code')) {
                    $event->route_info_code = $request->route_info['code'];
                }

                if ($request->filled('route_info.type') && $request->route_info['type'] == EventRouteInfoTypeEnum::RouteImage->value && $request->filled('route_info.media')) {
                    $this->attachMultipleUploadsToModel($event, $request->route_info['media'], UploadUseAsEnum::RouteInfo);
                }
            }

            if ($request->filled('what_is_included')) { // Save the What is Included Media Description & Media
                if ($request->has('what_is_included.description')) {
                    $event->what_is_included_description = $request->what_is_included['description'];
                }

                if ($request->filled('what_is_included.media')) {
                    $this->attachMultipleUploadsToModel($event, $request->what_is_included['media'], UploadUseAsEnum::WhatIsIncluded);
                }
            }

            if ($request->filled('socials') && $request->socials && $request->socials[0]['platform']) { // Update the event's socials
                $this->saveSocials($request, $event);
            }

            $this->saveMetaData($request, $event); // Save meta data

            if ($request->filled('faqs')) {
                $faqs['faqs'] = $request->faqs;
                $this->faqRepository->store($faqs, $event);
            }

            if ($event->isDirty(['route_info_description', 'route_info_code', 'what_is_included_description']))
                $event->save();

            DB::commit();
            $regions = explode(',', $request->region);
            foreach ($regions as $regionRef) {
                $event_region_link = new EventRegionLinking();
                $event_region_link->fill($request->all());
                $event_region_link->ref = Str::orderedUuid()->toString();
                $event_region_link->site_id = clientSiteId();
                $event_region_link->event_id = $event->id;
                $event_region_link->region_id = Region::where('ref', $regionRef)->value('id');
                $event_region_link->save(); // Save each link individually
            }

            if ($request->city)
            {
                $cities = explode(',', $request->city);
                foreach ($cities as $cityRef) {
                    $event_city_link = new EventCityLinking();
                    $event_city_link->fill($request->all());
                    $event_city_link->ref = Str::orderedUuid()->toString();
                    $event_city_link->site_id = clientSiteId();
                    $event_city_link->event_id = $event->id;
                    $event_city_link->city_id = City::where('ref', $cityRef)->value('id');
                    $event_city_link->save(); // Save each link individually
                }
            }
            if ($request->venue)
            {
                $venues = explode(',', $request->venue);
                foreach ($venues as $venueRef) {
                    $event_venue_link = new EventVenuesLinking();
                    $event_venue_link->fill($request->all());
                    $event_venue_link->ref = Str::orderedUuid()->toString();
                    $event_venue_link->site_id = clientSiteId();
                    $event_venue_link->event_id = $event->id;
                    $event_venue_link->venue_id = Venue::where('ref', $venueRef)->value('id');
                    $event_venue_link->save(); // Save each link individually
                }
            }
        } catch (QueryException $e) {
            DB::rollback();

            return $this->error('Unable to create the event! Please try again', 406, $e->getMessage());
        } catch (FileException $e) {
            DB::rollback();

            return $this->error('Unable to create the event! Please try again', 406, $e->getMessage());
        }

        return $this->success('Successfully created the event!', 200, new EventResource($event->refresh()->load(['address', 'city', 'eventCategories', 'eventManagers.user', 'eventThirdParties.eventCategories', 'excludedCharities', 'faqs', 'image', 'includedCharities', 'gallery', 'meta', 'routeInfoMedia', 'socials', 'whatIsIncludedMedia', 'venue'])));
    }

    /**
     * Edit an event
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string        $event
     * @return JsonResponse
     */
    public function edit($event): JsonResponse
    {
        $_event = (new CacheDataManager(
            $this->eventService,
            'edit',
            [$event]
        ))->getData();

        $users = (new CacheDataManager(
            new UserDataService(),
            'getEventManagers'
        ))->extraKey('event managers')
        ->getData();

        $region_ids = EventRegionLinking::where('event_id',$_event->id)->pluck('region_id')->toArray();
        $city_ids = EventCityLinking::where('event_id',$_event->id)->pluck('city_id')->toArray();
        $venue_ids = EventVenuesLinking::where('event_id',$_event->id)->pluck('venue_id')->toArray();
        $region_refs = Region::whereIn('id', $region_ids)
            ->get(['id', 'ref', 'name'])
            ->toArray();
        $city_refs = City::whereIn('id', $city_ids)
            ->get(['id', 'ref', 'name'])
            ->toArray();
        $venue_refs = Venue::whereIn('id', $venue_ids)
            ->get(['id', 'ref', 'name'])
            ->toArray();

        $_event['region_ids'] = Region::whereIn('id', collect($region_ids)->flatten()->toArray())->pluck('ref')->toArray();
        $_event['city_ids'] = City::whereIn('id', collect($city_refs)->flatten()->toArray())->pluck('ref')->toArray();
        $_event['venue_ids'] = Venue::whereIn('id', collect($venue_refs)->flatten()->toArray())->pluck('ref')->toArray();

        return $this->success('Edit the event', 200, [
            'event' => new EventResource($_event),
            'regions' => $region_refs,
            'cities' => $city_refs,
            'venues' => $venue_refs,
            'event_managers' => $users,
            'types' => EventTypeEnum::_options(),
            'partners' => BoolYesNoEnum::_options(),
            'states' => EventStateEnum::_options(),
            'robots' => MetaRobotsEnum::_options(),
            'socials' => SocialPlatformEnum::_options(),
            'reminders' => EventReminderEnum::_options(),
            'statuses' => BoolActiveInactiveEnum::_options(),
            'route_info_types' => EventRouteInfoTypeEnum::_options(),
            'event_withdrawal_weeks' => config('app.event_withdrawal_weeks'),
            'action_messages' => Event::$actionMessages
        ]);
    }

    /**
     * Update an event
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  EventUpdateRequest  $request
     * @param  Event               $event
     * @return JsonResponse
     */
    public function update(EventUpdateRequest $request, Event $event): JsonResponse
    {
        $_event = Event::with(['address:id,locationable_id,locationable_type,address', 'city:id,ref,name,slug', 'eventCategories:id,ref,name,slug', 'eventManagers:id,user_id', 'eventManagers.user:id,ref,first_name,last_name', 'excludedCharities:id,ref,name,slug', 'faqs.faqDetails.uploads', 'image', 'includedCharities:id,ref,name,slug', 'gallery', 'meta', 'region:id,ref,name,slug', 'routeInfoMedia', 'socials', 'whatIsIncludedMedia', 'venue:id,ref,name,slug', 'sponsor:id,ref,name,slug', 'serie:id,ref,name,slug'])
            ->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            })->withDrafted();

        try {
            $_event = $_event->where('ref', $event->ref)
                ->firstOrFail();

            try {
                DB::beginTransaction();

                $_event->fill($request->all());

                // if ($request->filled('region')) {
                //     $_event->region_id = Region::where('ref', $request->region)
                //         ->value('id');
                // }

                // if ($request->has('city')) {
                //     $_event->city_id = City::where('ref', $request->city)
                //         ->value('id');
                // }

                // if ($request->has('venue')) {
                //     $_event->venue_id = Venue::where('ref', $request->venue)
                //         ->value('id');
                // }

                if ($request->has('serie')) {
                    $_event->serie_id = Serie::where('ref', $request->serie)
                        ->value('id');
                }

                if ($request->has('sponsor')) {
                    $_event->sponsor_id = Sponsor::where('ref', $request->sponsor)
                        ->value('id');
                }

                $_event->save();

                if ($request->filled('location')) { // Create the event address
                    LocationHelper::saveAddress($event, $request);
                }

                if ($request->filled('event_managers')) { // Save the event managers
                    $eventManagers = EventManager::whereHas('user', function ($query) use ($request) {
                        $query->whereIn('ref', $request->event_managers);
                    })->pluck('id');

                    $_event->eventManagers()->sync($eventManagers);
                }

                if ($request->filled('categories')) {
                    $_categories = [];

                    foreach ($request->categories as $category) { // Link the event to the event categories
                        $categoryId = EventCategory::where('ref', $category['ref'])?->value('id');

                        unset($category['ref']);

                        $_categories[$categoryId] = $category;
                    }

                    $_event->eventCategories()->sync($_categories);

                    $_event->load(['eventCategories', 'eventThirdParties.eventCategories']);

                    $eventCategories = $_event->eventCategories->pluck('id')->all();

                    foreach($_event->eventThirdParties as $thirdParty) { // Detach the event categories that got removed from third parties
                        foreach ($thirdParty->eventCategories as $eventCategory) {
                            if (! in_array($eventCategory->id, $eventCategories)) { // Detach the event category
                                $thirdParty->eventCategories()->detach($eventCategory->id);
                            }
                        }
                    }
                }

                if ($request->filled('third_parties')) { // Update third party configurations
                    $_event->load(['eventThirdParties.eventCategories']);
                    
                    if ($event->eventThirdParties->isEmpty()) {
                        foreach ($request->third_parties as $thirdParty) {
                            $this->saveEventThirdParty($_event, (array) $thirdParty);
                        }
                    } else {
                        foreach ($request->third_parties as $thirdParty) {
                            $eventThirdParty = EventThirdParty::where('ref', $thirdParty['ref'])
                                ->where('event_id', $_event->id)
                                ->first();

                            $this->updateEventThirdParty($eventThirdParty, $_event, (array) $thirdParty);
                        }
                    }
                }

                if ($request->filled('charities') && $request->charities == EventCharitiesEnum::All->value) { // Delete included or excluded charities
                    $_event->includedExcludedCharities()->detach();
                } else { // Save included or excluded charities
                    if ($request->filled('_charities') && $request->filled('charities')) {

                        $_charities = Charity::whereIn('ref', $request->_charities)->pluck('id');

                        $_event->includedExcludedCharities()->syncWithPivotValues($_charities, ['type' => CharityEventTypeEnum::from($request->charities)]);
                    }
                }

                if ($request->filled('image')) { // Save the event's image
                    $this->attachSingleUploadToModel($_event, $request->image);
                }

                if ($request->filled('gallery')) { // Save the event's gallery | TODO: Look deeper into these images upload and improve on it by adding a possibility to add an image(s) without deleting the existing ones (you may use a different route for this). Also use a route to delete an image.
                    $this->attachMultipleUploadsToModel($_event, $request->gallery, UploadUseAsEnum::Gallery);
                }

                if ($request->filled('route_info')) { // Save the Route Infomation & (Media or Embed Link)
                    if ($request->has('route_info.description')) {
                        $_event->route_info_description = $request->route_info['description'];
                    }

                    if ($request->filled('route_info.type') && $request->route_info['type'] == EventRouteInfoTypeEnum::EmbedCode->value && $request->has('route_info.code')) {
                        $_event->route_info_code = $request->route_info['code'];

                        if ($_event->routeInfoMedia) { // Only one (code or media) can exists at the time
                            foreach ($_event->routeInfoMedia as $image) { // Delete the existing images on disk
                                $this->detachUpload($_event, $image->ref);
                            }
                        }
                    }

                    if ($request->filled('route_info.type') && $request->route_info['type'] == EventRouteInfoTypeEnum::RouteImage->value && $request->filled('route_info.media')) {
                        $_event->route_info_code = null; // Only one (code or media) can exists at the time

                        $this->attachMultipleUploadsToModel($_event, $request->route_info['media'], UploadUseAsEnum::RouteInfo);
                    }
                }

                if ($request->filled('what_is_included')) { // Save the What is Included Media Description & Media
                    if ($request->has('what_is_included.description')) {
                        $_event->what_is_included_description = $request->what_is_included['description'];
                    }

                    if ($request->filled('what_is_included.media')) {
                        $this->attachMultipleUploadsToModel($_event, $request->what_is_included['media'], UploadUseAsEnum::WhatIsIncluded);
                    }
                }

                if ($request->filled('socials') && $request->socials && $request->socials[0]['platform']) { // Update the event's socials
                    $this->saveSocials($request, $_event);
                }

                $this->saveMetaData($request, $_event); // Save meta data

                if ($request->filled('faqs')) {
                    $this->faqRepository->update($request->validated(), $_event);
                }

                if ($_event->isDirty(['route_info_description', 'route_info_code', 'what_is_included_description']))
                    $_event->save();

                DB::commit();

                if ($request->filled('region')) {
                    EventRegionLinking::where('event_id', Event::where('ref', $request->ref)->value('id'))->delete();
                    $regions = explode(',', $request->region);
                    foreach ($regions as $regionRef) {
                        $event_region_link = new EventRegionLinking();
                        $event_region_link->fill($request->all());
                        $event_region_link->ref = Str::orderedUuid()->toString();
                        $event_region_link->site_id = clientSiteId();
                        $event_region_link->event_id = $event->id;
                        $event_region_link->region_id = Region::where('ref', $regionRef)->value('id');
                        $event_region_link->save(); // Save each link individually
                    }
                }
                if ($request->filled('city')) {
                    EventCityLinking::where('event_id', Event::where('ref', $request->ref)->value('id'))->delete();
                    $cities = explode(',', $request->city);
                    foreach ($cities as $cityRef) {
                        $event_city_link = new EventCityLinking();
                        $event_city_link->fill($request->all());
                        $event_city_link->ref = Str::orderedUuid()->toString();
                        $event_city_link->site_id = clientSiteId();
                        $event_city_link->event_id = $event->id;
                        $event_city_link->city_id = City::where('ref', $cityRef)->value('id');
                        $event_city_link->save(); // Save each link individually
                    }
                }
                if ($request->filled('venue')) {
                    EventVenuesLinking::where('event_id', Event::where('ref', $request->ref)->value('id'))->delete();
                    $venues = explode(',', $request->venue);
                    foreach ($venues as $venueRef) {
                        $event_venue_link = new EventVenuesLinking();
                        $event_venue_link->fill($request->all());
                        $event_venue_link->ref = Str::orderedUuid()->toString();
                        $event_venue_link->site_id = clientSiteId();
                        $event_venue_link->event_id = $event->id;
                        $event_venue_link->venue_id = Venue::where('ref', $venueRef)->value('id');
                        $event_venue_link->save(); // Save each link individually
                    }
                }

                CacheDataManager::flushAllCachedServiceListings($this->eventService);
                CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
            } catch (QueryException $e) {
                DB::rollback();

                if (Str::contains($e->getMessage(), "Integrity constraint violation: 1451 Cannot delete or update a parent row: a foreign key constraint fails") && Str::contains($e->getMessage(), "`participants`, CONSTRAINT `participants_event_event_category_id_foreign` FOREIGN KEY (`event_event_category_id`)")) { // In case a participant exists under an event category that was removed while updating the event.
                    return $this->error('Unable to update the event! A participant exists under the event category you removed. You cannot remove an event category from an event when it has a participant registered under it. Either delete the participants or change their event category before attempting to remove the event category from the event.', 406, $e->getMessage());
                }

                return $this->error('Unable to update the event! Please try again.', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404, $e->getMessage());
        }

        $event = $this->eventService->modelWithAppendedAnalyticsAttribute($_event->refresh()->load([
            'address:id,locationable_id,locationable_type,address,coordinates',
            'city:id,ref,name,slug',
            'eventCategories:id,ref,name,slug',
            'eventThirdParties.eventCategories',
            'eventThirdParties.partnerChannel.partner:id,ref,name,code',
            'eventManagers:id,user_id',
            'eventManagers.user:id,ref,first_name,last_name',
            'excludedCharities:id,ref,name,slug',
            'faqs.faqDetails.uploads',
            'image',
            'includedCharities:id,ref,name,slug',
            'gallery',
            'meta',
            'region:id,ref,name,slug',
            'routeInfoMedia',
            'socials',
            'whatIsIncludedMedia',
            'venue:id,ref,name,slug',
            'sponsor:id,ref,name,slug',
            'serie:id,ref,name,slug'
        ]));

        return $this->success('Successfully updated the event!', 200, new EventResource($event));
    }

    /**
     * Update the registration fields (mandatory & optional) of an event
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  EventRegFieldsUpdateRequest  $request
     * @param  string                       $event
     * @return JsonResponse
     */
    public function updateRegistrationFields(EventRegFieldsUpdateRequest $request, string $event): JsonResponse
    {
        $_event = Event::whereHas('eventCategories', function ($query) {
            $query->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            });
        });

        try {
            $_event = $_event->where('ref', $event)
                ->withDrafted()
                ->firstOrFail();

            try {
                DB::beginTransaction();

                $_event->fill($request->only(['reg_first_name', 'reg_last_name', 'reg_email', 'reg_gender', 'reg_dob', 'reg_phone', 'reg_preferred_heat_time', 'reg_raced_before', 'reg_estimated_finish_time', 'reg_tshirt_size', 'reg_age_on_race_day', 'reg_month_born_in', 'reg_nationality', 'reg_occupation', 'reg_address', 'reg_city', 'reg_state', 'reg_postcode', 'reg_country', 'reg_emergency_contact_name', 'reg_emergency_contact_phone', 'reg_passport_number', 'reg_family_registrations', 'reg_minimum_age', 'born_before', 'reg_ethnicity', 'reg_weekly_physical_activity', 'reg_speak_with_coach', 'reg_hear_from_partner_charity', 'reg_reason_for_participating']));

                if ($request->filled('reg_preferred_heat_time') && !$request->reg_preferred_heat_time) {
                    $_event->custom_preferred_heat_time_start = null;
                    $_event->custom_preferred_heat_time_end = null;
                } else if ($request->filled('custom_preferred_heat_time') && (isset($request->custom_preferred_heat_time['custom_preferred_heat_time_start']) && isset($request->custom_preferred_heat_time['custom_preferred_heat_time_end']))) {
                    $_event->custom_preferred_heat_time_start = $request->custom_preferred_heat_time['custom_preferred_heat_time_start'];
                    $_event->custom_preferred_heat_time_end = $request->custom_preferred_heat_time['custom_preferred_heat_time_end'];
                }

                $_event->save();

                DB::commit();
            } catch (QueryException $e) {
                DB::rollback();

                return $this->error('Unable to update the event registration fields! Please try again.', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('Successfully updated the event registration fields!', 200, new EventResource($_event->refresh()));
    }

    /**
     * Mark an event as published
     *
     * @bodyParam ids array required The ids of the events to be marked as published. Example: [1, 2, 3]
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function markAsPublished(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsPublishedValidationRules('events'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();

            Event::whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            })->whereIntegerInRaw('id', $request->ids)
            ->onlyDrafted()
            ->markAsPublished();

            DB::commit();

            CacheDataManager::flushAllCachedServiceListings($this->eventService);
            CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
            (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();

            return $this->success('Successfully marked the events as published!', 200);
        } catch (QueryException $e) {
            DB::rollback();
            return $this->error('Unable to mark the events as published! Please try again.', 406, $e->getMessage());
        }
    }

    /**
     * Mark an event as draft
     *
     * @bodyParam ids array required The ids of the events to be marked as draft. Example: [1, 2, 3]
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function markAsDraft(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->markAsDraftValidationRules('events'));

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            DB::beginTransaction();

            Event::whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            })->whereIntegerInRaw('id', $request->ids)
            ->markAsDraft();

            DB::commit();

            CacheDataManager::flushAllCachedServiceListings($this->eventService);
            CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
            (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();

            return $this->success('Successfully marked the events as draft!', 200);
        } catch (QueryException $e) {
            DB::rollback();
            return $this->error('Unable to mark the events as draft! Please try again.', 406, $e->getMessage());
        }
    }

    /**
     * Delete one or many events (Soft delete)
     *
     * @param  EventDeleteRequest $request
     * @return JsonResponse
     */
    public function destroy(EventDeleteRequest $request): JsonResponse
    {
        $events = Event::whereHas('eventCategories', function ($query) {
            $query->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            });
        });

        try {
            $events = $events->whereIn('ref', $request->refs)
                ->withDrafted()->get();

            if (! $events->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                $forbiddenEvents = [];

                foreach ($events as $event) {
                    if ($event->participants()->exists()) {
                        $forbiddenEvents[] = $event->name;
                    } else {
                        $event->delete();
                    }
                }

                DB::commit();
            } catch(QueryException $e) {
                DB::rollback();
                return $this->error('Unable to delete the '. static::singularOrPlural(['event', 'events'], $request->refs) .'! Please try again.', 406, $e->getMessage());
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The '. static::singularOrPlural(['event was', 'events were'], $request->refs) .' not found!', 404);
        }

        if (count($forbiddenEvents)) {
            $names = implode(', ', $forbiddenEvents);
            $message = "Successfully deleted the events! However, some events (" . $names . ") have participants registered on them and could not be deleted.";
        } else {
            $message = 'Successfully deleted the '. static::singularOrPlural(['event', 'events'], $request->refs) . '!';
        }

        return $this->success($message, 200, new EventResource($events));
    }

    /**
     * Restore one or many events
     *
     * @param  EventRestoreRequest $request
     * @return JsonResponse
     */
    public function restore(EventRestoreRequest $request): JsonResponse
    {
        $events = Event::whereHas('eventCategories', function ($query) {
            $query->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            });
        });

        try {
            $events = $events->whereIn('ref', $request->refs)
                ->withDrafted()
                ->onlyTrashed()
                ->get();

            if (! $events->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($events as $event) { // TODO: @tsaffi: Ensure the event and the given category exists and have available places
                    $event->restore();
                }

                DB::commit();
            } catch(QueryException $e) {
                DB::rollback();
                return $this->error('Unable to restore the '. static::singularOrPlural(['event', 'events'], $request->refs) .'! Please try again.', 406, $e->getMessage());
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The '. static::singularOrPlural(['event was', 'events were'], $request->refs) .' not found!', 404);
        }

        return $this->success('Successfully restored the '. static::singularOrPlural(['event', 'events'], $request->refs), 200, new EventResource($events));
    }

    /**
     * Delete one or many events (Permanently)
     * Only the administrator can delete an event permanently.
     *
     * @param  EventDeleteRequest $request
     * @return JsonResponse
     */
    public function destroyPermanently(EventDeleteRequest $request): JsonResponse
    {
        if (! AccountType::isAdmin()) { // Only the administrator can delete an event permanently.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $events = Event::with(['image', 'gallery'])
            ->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            });

        try {
            $events = $events->whereIn('ref', $request->refs)
                ->withDrafted()
                ->withTrashed()
                ->get();

            if (! $events->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($events as $event) {
                    $event->forceDelete();
                }

                DB::commit();
            } catch(QueryException $e) {
                DB::rollback();

                // TODO: Add a cascade query param to this method so that when passed, the logic force deletes participants, before force deleting the event.
                // Discuss this with the frontend guys and ensure they display a third prompt to the user (with the msg below and "Would you like to delete all the x participants associated with this event?") in case the force delete had this exception. The action to perform will now be done with the help of the cascade query param after the user would have clicked on yes.

                return $this->error('Unable to delete the '. static::singularOrPlural(['event', 'events'], $request->refs) .' permanently! Events having participants can\'t be deleted permanently unless these participants are first deleted permanently.', 406, $e->getMessage());
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The '. static::singularOrPlural(['event was', 'events were'], $request->refs) .' not found!', 404);
        }

        return $this->success('Successfully deleted the '. static::singularOrPlural(['event', 'events'], $request->refs) .' permanently', 200, new EventResource($events->load(['image', 'gallery'])));
    }

    /**
     * Remove the event image or an image from the event's gallery.
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam upload_ref string required The ref of the upload. Example: 97ad9df6-d927-4a44-8fec-3daacee89678
     *
     * @param  string        $event
     * @param  string        $upload_ref
     * @return JsonResponse
     */
    public function removeImage(string $event, string $upload_ref): JsonResponse
    {
        $_event = Event::with('uploads')
            ->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            });

        try {
            $_event = $_event->where('ref', $event)
                ->firstOrFail();

            try {
                $upload = $_event->uploads()->where('ref', $upload_ref)->firstOrFail();

                try {
                    $this->detachUpload($_event, $upload->ref);

                    CacheDataManager::flushAllCachedServiceListings($this->eventService);
                    CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                    CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                    (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
                } catch (QueryException $e) {
                    return $this->error('Unable to delete the image! Please try again', 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {
                return $this->error('The image was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        $_event->load(['image', 'images']);

        $_event = $_event->only(['id', 'ref', 'name', 'status', 'slug', 'image', 'images']);

        return $this->success('Successfully deleted the image!', 200, new EventResource($_event));
    }

    /**
     * Duplicate an event
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string        $event
     * @return JsonResponse
     */
    public function duplicate(string $event): JsonResponse
    {
        $_event = Event::with(['city', 'eventCategories', 'venue'])
            ->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            });

        try {
            $_event = $_event->where('ref', $event)
                ->firstOrFail();

            try {
                DB::beginTransaction();

                $clone = $_event->duplicate();

                $clone->load(['city:id,ref,name,slug', 'eventCategories', 'eventDetails', 'eventManagers.user:id,ref,first_name,last_name', 'image', 'gallery', 'promotionalEventCategories', 'routeInfoMedia', 'uploads', 'venue:id,ref,name,slug', 'whatIsIncludedMedia']);

                // TODO: The cloner currently creates a copy of the files on the disk when cloning the uploads relation. Check the onCloning method on the Upload model.
                // It is better to either use the filesystem David is working on once it is ready.
                
                // this is for save address
                $fetchLocation = DB::table('locations')
                    ->where('locationable_type', 'App\Modules\Event\Models\Event')
                    ->where('locationable_id', $_event->id)
                    ->first();
                $fetchEventId = DB::table('events')
                    ->where('slug', $clone->slug)
                    ->first();
                if ($fetchLocation && $fetchEventId) {
                    DB::table('locations')->insert([
                        'locationable_type' => $fetchLocation->locationable_type,
                        'locationable_id' => $fetchEventId->id,
                        'ref' => $fetchLocation->ref,
                        'address' => $fetchLocation->address,
                        'use_as' => $fetchLocation->use_as,
                        'coordinates' => $fetchLocation->coordinates,
                    ]);
                }

                DB::commit();

                CacheDataManager::flushAllCachedServiceListings($this->eventService);
                CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
            } catch (QueryException $e) {
                DB::rollback();
                return $this->error('Unable to duplicate the event! Please try again', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('Successfully duplicated the event!', 201, new EventResource($clone));
    }

    /**
     * Archive an event
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string        $event
     * @return JsonResponse
     */
    public function archive(string $event): JsonResponse
    {
        $_event = Event::with(['eventCategories'])
            ->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            });

        try {
            $_event = $_event->where('ref', $event)
                ->firstOrFail();

            try {
                if ($_event->archived) {
                    throw new ModelNotFoundException('The event has already been archived');
                }

                try {
                    $result = Event::archive($_event);

                    if (! $result->status) {
                        // throw new QueryException('Unable to archive the event. Please try again');
                        throw new ModelNotFoundException('Unable to archive the event. Please try again');
                    }

                    // Notify admin so that the newly created event can be reviewed (NB: It has already been published)
                    event(new EventArchivedEvent($result->event, $result->clone));

                    CacheDataManager::flushAllCachedServiceListings($this->eventService);
                    CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                    CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                    (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
                } catch (ModelNotFoundException $e) {
                    return $this->error('Unable to archive the event! Please try again', 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {
                return $this->error($e->getMessage(), 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('Successfully archived the event!', 201, $result);
    }

    /**
     * Archive multiple events
     *
     * @return JsonResponse
     */
    public function archiveEvents(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // The event ids. Example: [975decbc-bad7-4017-874d-494f5fba4eb5, 975decb8-df40-4f1b-a907-ab5243f06c95, 975ded84-f9ac-4366-8b9e-9f36711ed0ef, 975ded92-82ee-42ae-aebe-c2012fd51752]
            'event_refs' => ['required', 'array', 'exists:events,ref'],
            'event_refs.*' => ['string'],
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $events = Event::with(['eventCategories'])
            ->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            });

        try {
            $events = $events->whereIn('ref', $request->event_refs)
                ->where('archived', Event::INACTIVE)
                ->get();

            if ($events->isEmpty()) {
                throw new ModelNotFoundException('The ' . static::singularOrPlural(['event was', 'events were'], $request->event_refs) . ' not found!');
            }

            $result = [];

            foreach ($events as $event) {
                $result[] = Event::archive($event);
            }

            // Notify admin so that the current events can be reviewed and then published
            event(new EventsArchivedEvent($result));

            CacheDataManager::flushAllCachedServiceListings($this->eventService);
            CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
            (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
        } catch (ModelNotFoundException $e) {
            return $this->error('The ' . static::singularOrPlural(['event was', 'events were'], $request->event_refs) . ' not found!', 404, $e->getMessage());
        }

        return $this->success('Successfully archived the ' . static::singularOrPlural(['event', 'events'], $request->event_refs).'!', 201, $result);
    }

    /**
     * Export events
     *
     * @queryParam state string Filter by state. Must be one of live, expired, archived. Example: live
     * @queryParam category string Filter by event category ref. No-example
     * @queryParam region string Filter by region ref. No-example
     * @queryParam city string Filter by city ref. No-example
     * @queryParam venue string Filter by venue ref. No-example
     * @queryParam event_experience string Filter by event_experience ref. No-example
     * @queryParam year int Filter by year. No-example
     * @queryParam month int Filter by month. No-example
     * @queryParam status boolean Filter by status. Example: true
     * @queryParam type string Filter by type. Example: rolling, standalone
     * @queryParam partner_event boolean Filter by partner_event. Example: true
     * @queryParam term string Filter by term. The term to search for. No-example
     * @queryParam ids integer[] Filter by ids. Must be an array of ids. Example: [148, 153]
     * @queryParam deleted string Specifying how to interact with soft-deleted items. Example: with
     * @queryParam order_by string Specifying method of ordering query. Multiple values can be provided by listing items separated by comma. Example: name:asc,start_date:asc,end_date:desc
     * @queryParam year string Specifying year filter for when user was created. Example: 2023
     * @queryParam site string Filter by site ref. The site to search in. This parameter is only available to users of role administrator when making requests from sportsmediaagency.com (That is, when making request to get data of the whole application - all the platforms). No-example
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param EventListingQueryParamsRequest $request
     * @return BinaryFileResponse|JsonResponse|StreamedResponse
     */
    public function export(EventListingQueryParamsRequest $request): BinaryFileResponse|JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! (AccountType::isAdmin() || AccountType::isContentManager())) {
            return $this->error('You do not have permission to access this resource!', 403);
        }

        try {
            return $this->eventService->downloadCsv($request);
        } catch (ExportableDataMissingException $exception) {
            Log::error($exception);
            return $this->error($exception->getMessage(), $exception->getCode());
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->error('An error occurred while exporting events\' data.', 400);
        }
    }

    /**
     * The charities summary for an event (grouped by it's event categories)
     *
     * TODO: @tsaffi - Revise this logic based on the similar revision made on SFC
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string        $event
     * @return JsonResponse
     */
    public function charitiesSummary(string $event): JsonResponse
    {
        if (! (AccountType::isAdmin() || AccountType::isContentManager())) {
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $_event = Event::with(['eventCategories', 'eventManagers.user', 'excludedCharities', 'image', 'includedCharities', 'gallery', 'socials']);

        $_event = $_event->whereHas('eventCategories', function ($query) {
            $query->whereHas('site', function ($query) {
                $query->hasAccess()
                    ->makingRequest();
            });
        });

        try {
            $_event = $_event->where('ref', $event)
                ->firstOrFail();

            try {
                $data = [];
                $total = (object) [];
                $total->places = 0;
                $total->participants_count = 0;
                $total->charity_participants_count = 0;

                foreach ($_event->eventCategories as $eventCategory) { // loop through the event categories and group the data by event category.
                    $participants = Participant::with('user:id,first_name,last_name,email')
                        ->where('event_event_category_id', $eventCategory->pivot->id)
                        ->get();

                    // Get the charities having participants in the event & event category
                    $charities = Charity::select('id', 'ref', 'name', 'slug')
                        ->with('latestCharityMembership')
                        ->withCount(['participants' => function ($query) use ($eventCategory) {
                            $query->where('event_event_category_id', $eventCategory->pivot->id);
                        }])->whereHas('participants', function ($query) use ($eventCategory) {
                            $query->where('event_event_category_id', $eventCategory->pivot->id);
                    })->get();

                    $subTotal = (object) [];
                    $subTotal->places = 0;
                    $subTotal->participants_count = $participants->count();
                    $subTotal->charity_participants_count = 0;

                    foreach ($charities as $charity) {
                        $charity->places = $eventCategory->pivot($charity);
                        $charity->athletes_paid_for = Event::charityPaidFor($eventCategory->pivot, $charity);
                        $charity->athletes_to_pay_for = Event::charityToPayFor($eventCategory->pivot, $charity);

                        $subTotal->places += $charity->places;
                        $subTotal->charity_participants_count += $charity->participants_count;
                    }

                    $data[] = [
                        'event_category' => $eventCategory,
                        'total' => $subTotal,
                        'charities' => $charities,
                        'participants' => $participants
                    ];

                    $total->places += $subTotal->places;
                    $total->participants_count += $subTotal->participants_count;
                    $total->charity_participants_count += $subTotal->charity_participants_count;
                }
            } catch (QueryException $e) {
                return $this->error('An error occured. Please try again!', 406, $e->getMessage());
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('The charities summary for the event!', 200, [
            'count' => $charities->count(),
            'total' => $total,
            'event' => $_event,
            'data' => $data
        ]);
    }

    /**
     * Toggle charity total places notifications
     * Whether or not to notify a charity when it's availble places reduces to certain threshold values.
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam charity_ref string required The ref of the charity. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  Request       $request
     * @param  string        $event
     * @param  string        $charity_ref
     * @return JsonResponse
     */
    public function toggleTotalPlacesNotifications(Request $request, string $event, string $charity_ref): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notify' => ['required', 'boolean']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $_event = Event::whereHas('eventCategories', function ($query) {
            $query->whereHas('site', function ($query) {
                $query->makingRequest();
            });
        });

        try {
            $_event = $_event->where('ref', $event)
                ->firstOrFail();

            try {
                $charity = Charity::filterByAccess();

                $charity = $charity->where('ref', $charity_ref)
                    ->firstOrFail();

                $notification = TotalPlacesNotification::where('event_id', $_event->id)
                    ->where('charity_id', $charity->id)
                    ->firstOrNew();

                try {
                    if ($request->notify) {
                        $notification = $notification->updateOrCreate(
                            [
                                'event_id' => $_event->id,
                                'charity_id' => $charity->id,
                            ]
                        );
                        $message = 'on';
                    } else {
                        $notification?->delete();
                        $message = 'off';
                    }

                } catch (QueryException $e) {
                    return $this->error('Unable to update notification settings! Please try again.', 406, $e->getMessage());
                }

            } catch (ModelNotFoundException $e) {
                return $this->error('The charity was not found!', 404);
            }

        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success("Successfully turned {$message} total places notification", 200, $notification);
    }

    /**
     * Add an event to promotional pages
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string         $event
     * @return JsonResponse
     */
    public function addToPromotionalPages(string $event): JsonResponse
    {
        $_event = Event::with('uploads')
            ->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->hasAccess()
                        ->makingRequest();
                });
            });

        try {
            $_event = $_event->where('ref', $event)
                ->firstOrFail();

            $this->dispatch(new AddEventToPromotionalPagesJob($_event));

        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success("Successfully added the event to promotional pages!", 200, new EventResource($_event));
    }

    /**
     * Get the event's custom fields.
     *
     * @queryParam term string Filter by term. The term to search for. No-example
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  Request       $request
     * @param  string         $event
     * @return JsonResponse
     */
    public function customFields(Request $request, string $event): JsonResponse
    {
        $_event = Event::with(['eventCustomFields' => function ($query) use ($request) {
            $query->when(
                $request->filled('term'),
                fn ($query) => $query->where('name', 'like', "%$request->term%")
            );
        }])->where('ref', $event);

        $_event = $_event->whereHas('eventCategories', function ($query) {
            $query->whereHas('site', function ($query) {
                $query->makingRequest();
            });
        });

        try {
            $_event = $_event->firstOrFail();

        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('The event custom fields', 200, new EventCustomFieldResource($_event->eventCustomFields));
    }

    /**
     * Create an event custom field for registration
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string         $event
     * @return JsonResponse
     */
    public function createCustomField(string $event): JsonResponse
    {
        try {
            $_event = Event::where('ref', $event)
                ->firstOrFail();

        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('Create an event custom field!', 200, [
            'rules' => EventCustomFieldRuleEnum::_options(),
            'types' => EventCustomFieldTypeEnum::_options(),
            'statuses' => BoolActiveInactiveEnum::_options(),
            'event' => new EventResource($_event)
        ]);
    }

    /**
     * Store an event custom field for registration
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  EventCustomFieldCreateRequest  $request
     * @param  string                         $event
     * @return JsonResponse
     */
    public function storeCustomField(EventCustomFieldCreateRequest $request, string $event): JsonResponse
    {
        $_event = Event::with(['eventCustomFields']);

        try {
            $_event = $_event->where('ref', $event)
                ->firstOrFail();

            $ecf = EventCustomField::firstOrNew([ // Ensure the custom field is unique
                'event_id' => $_event->id,
                'slug' => Str::slug($request->name)
            ]);

            try {
                if ($ecf->exists) {
                    throw new ModelNotFoundException('The custom field already exist! Please add a different one.');
                }

                $ecf->fill($request->all());
                $ecf->save();

                CacheDataManager::flushAllCachedServiceListings($this->eventService);
                CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
            }  catch (QueryException $e) {
                return $this->error('Unable to add the custom field! Please try again.', 406, $e->getMessage());
            }  catch (ModelNotFoundException $e) {
                return $this->error($e->getMessage(), 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('Successfully added the custom field!', 201, new EventCustomFieldResource($ecf->refresh()));
    }

    /**
     * Edit an event custom field for registration
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam event_custom_field_ref string required The ref of the event custom field. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string        $event
     * @param  string        $eventCustomField
     * @return JsonResponse
     */
    public function editCustomField(string $event, string $eventCustomField): JsonResponse
    {
        try {
            $_event = Event::where('ref', $event)
                ->firstOrFail();

            try {
                $ecf = $_event->eventCustomFields()->where('ref', $eventCustomField)->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return $this->error('The event custom field was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('Edit a custom field!', 200, [
            'rules' => EventCustomFieldRuleEnum::_options(),
            'types' => EventCustomFieldTypeEnum::_options(),
            'event_custom_field' => new EventCustomFieldResource($ecf)
        ]);
    }

    /**
     * Update an event custom field for registration
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam event_custom_field_ref string required The ref of the event custom field. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  EventCustomFieldUpdateRequest  $request
     * @param  string                         $event
     * @param  string                         $eventCustomField
     * @return JsonResponse
     */
    public function updateCustomField(EventCustomFieldUpdateRequest $request, string $event, string $eventCustomField): JsonResponse
    {
        try {
            $_event = Event::select(['id', 'ref', 'name', 'slug']);
                $_event = $_event->where('ref', $event)
                    ->firstOrFail();
            try {
                $ecf = $_event->eventCustomFields()
                    ->where('ref', $eventCustomField)
                    ->firstOrFail();

                try {
                    $ecf->fill($request->all());
                    $ecf->save();

                    CacheDataManager::flushAllCachedServiceListings($this->eventService);
                    CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                    CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                    (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
                }  catch (QueryException $e) {
                    return $this->error('Unable to update the event custom field! Please try again.', 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {
                return $this->error('The event custom field was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('Successfully updated the custom field!', 200, new EventCustomFieldResource($ecf->refresh()));
    }

    /**
     * Toggle the status of a custom field for registration
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam event_custom_field_ref string required The ref of the event custom field. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  Request       $request
     * @param  string        $event
     * @param  string        $eventCustomField
     * @return JsonResponse
     */
    public function toggleCustomFieldStatus(Request $request, string $event, string $eventCustomField): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'boolean']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $_event = Event::with(['eventCustomFields']);

        try {
            $_event = $_event->where('ref', $event)
                ->firstOrFail();

            try {
                $ecf = $_event->eventCustomFields()->where('ref', $eventCustomField)->firstOrFail();

                try {
                    $ecf->status = $request->status;
                    $ecf->save();

                    $ecf->refresh();

                    $message = $request->status ? "activate" : "deactive";

                    CacheDataManager::flushAllCachedServiceListings($this->eventService);
                    CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                    CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                    (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
                }  catch (QueryException $e) {
                    return $this->error("Unable to {$message} the event custom field! Please try again.", 406, $e->getMessage());
                }
            } catch (ModelNotFoundException $e) {
                return $this->error('The event custom field was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success("Successfully {$message}d the custom field!", 200, new EventCustomFieldResource($ecf));
    }

    /**
     * Delete one or many event custom fields (Soft delete)
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  EventCustomFieldDeleteRequest  $request
     * @param  string                         $event
     * @return JsonResponse
     */
    public function destroyCustomField(EventCustomFieldDeleteRequest $request, string $event): JsonResponse
    {
        $ecf = EventCustomField::whereHas('event', function ($query) use ($event) {
                $query->where('ref', $event)
                    ->whereHas('eventCategories', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->hasAccess()
                                ->makingRequest();
                        });
                    });
            });

        try {
            $ecf = $ecf->whereIn('ref', $request->refs)
                ->get();

            if (! $ecf->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($ecf as $cf) {
                    $cf->delete();
                }

                DB::commit();

                CacheDataManager::flushAllCachedServiceListings($this->eventService);
                CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
            } catch(QueryException $e) {
                DB::rollback();
                return $this->error('Unable to delete the '. static::singularOrPlural(['event custom field', 'event custom fields'], $request->refs) .'! Please try again.', 406, $e->getMessage());
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The '. static::singularOrPlural(['event custom field was', 'event custom fields were'], $request->refs) .' not found!', 404);
        }

        return $this->success('Successfully deleted the '. static::singularOrPlural(['event custom field', 'event custom fields'], $request->refs), 200);
    }

    /**
     * Delete one or more event custom fields (Permanently)
     * Only the administrator can delete a custom event field permanently.
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  EventCustomFieldDeleteRequest  $request
     * @param  string             $event
     * @return JsonResponse
     */
    public function destroyCustomFieldPermanently(EventCustomFieldDeleteRequest $request, string $event): JsonResponse
    {
        if (! AccountType::isAdmin()) { // Only the administrator can delete an event permanently.
            return $this->error('You do not have permission to access this resource!', 403);
        }

        $ecf = EventCustomField::withTrashed()
            ->whereHas('event', function ($query) use ($event) {
                $query->where('ref', $event)
                    ->whereHas('eventCategories', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->hasAccess()
                                ->makingRequest();
                        });
                    });
            });

        try {
            $ecf = $ecf->whereIn('ref', $request->refs)
                ->get();

            if (! $ecf->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($ecf as $cf) {
                    $cf->forceDelete();
                }

                DB::commit();

                CacheDataManager::flushAllCachedServiceListings($this->eventService);
                CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
            } catch(QueryException $e) {
                DB::rollback();
                return $this->error('Unable to delete the '. static::singularOrPlural(['event custom field', 'event custom fields'], $request->refs) .' permanently! Please try again.', 406, $e->getMessage());
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The '. static::singularOrPlural(['event custom field was', 'event custom fields were'], $request->refs) .' not found!', 404);
        }

        return $this->success('Successfully deleted the '. static::singularOrPlural(['event custom field', 'event custom fields'], $request->refs). ' permanently', 200);
    }

    /**
     * Get the event's partners (third party integration).
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @queryParam term string Filter by term. The term to search for. No-example
     *
     * @param  Request       $request
     * @param  string        $event
     * @return JsonResponse
     */
    public function thirdParties(Request $request, string $event): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'term' => ['sometimes', 'nullable', 'string']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        $_event = Event::with(['eventThirdParties' => function ($query) use ($request) {
            $query->with(['eventCategories', 'partnerChannel.partner.upload']);

            if ($request->filled('term')) {
                $query->whereHas('partnerChannel', function ($query) use ($request) {
                    $query->where(function ($query) use ($request) {
                        $query->where('name', 'like', "%{$request->term}%")
                            ->orWhereHas('partner', function ($query) use ($request) {
                                $query->where('name', 'like', "%{$request->term}%");
                            });
                    });
                });
            }
        }])->where('ref', $event);

        $_event = $_event->whereHas('eventCategories', function ($query) {
            $query->whereHas('site', function ($query) {
                $query->makingRequest();
            });
        });

        try {
            $_event = $_event->firstOrFail();

        } catch (ModelNotFoundException $e) {

            return $this->error('The event was not found!', 404);
        }

        return $this->success('The event third parties', 200, [
            'event_third_parties' => new EventThirdPartyResource($_event->eventThirdParties),
            'query_params' => $request->all()
        ]);
    }


    /**
     * Create an event's partner (third party integration)
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string         $event
     * @return JsonResponse
     */
    public function createThirdParty(string $event): JsonResponse
    {
        try {
            $_event = Event::with(['eventCategories', 'eventThirdParties.eventCategories'])
                ->where('ref', $event)
                ->firstOrFail();

        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('Create an event partner!', 200, [
            'event' => new EventResource($_event)
        ]);
    }

    /**
     * Store an event's partner (third party integration)
     * Only one partner channel can be added per partner for a given event
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  EventThirdPartyCreateRequest  $request
     * @param  Event                         $event
     * @return JsonResponse
     */
    public function storeThirdParty(EventThirdPartyCreateRequest $request, Event $event): JsonResponse
    {
        $_event = Event::with(['eventThirdParties']);

        try {
            $_event = $_event->where('ref', $event->ref)
                ->firstOrFail();

            $partner = Partner::whereHas('partnerChannels', function ($query) use ($request) { // Get the partner to which the partner channel belongs
                $query->where('ref', $request->partner_channel);
            })->first();

            $_eventThirdParty = EventThirdParty::where('event_id', $_event->id) // Ensure only one partner channel can be added per partner for a given event
                ->whereHas('partnerChannel', function ($query) use ($partner) {
                    $query->whereHas('partner', function ($query) use ($partner) {
                        $query->where('id', $partner?->id);
                    });
                });

            try {
                if ($_eventThirdParty->exists()) {
                    throw new ModelNotFoundException('A channel belonging to this partner already exist! Only one partner channel can be added per partner for a given event. Please update the existing one.');
                }

                $_eventThirdParty = $this->saveEventThirdParty($_event, (array) $request->all());

                CacheDataManager::flushAllCachedServiceListings($this->eventService);
                CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
            }  catch (QueryException $e) {
                return $this->error('Unable to add the event partner! Please try again.', 406, $e->getMessage());
            }  catch (ModelNotFoundException $e) {
                return $this->error($e->getMessage(), 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('Successfully added the event partner!', 201, new EventThirdPartyResource($_eventThirdParty->load(['eventCategories', 'partnerChannel.partner.upload'])->refresh()));
    }

    /**
     * Edit an event's partner (third party integration)
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam event_third_party_ref string required The ref of the event third party. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  string           $event
     * @param  EventThirdParty  $eventThirdParty
     * @return JsonResponse
     */
    public function editThirdParty(string $event, EventThirdParty $eventThirdParty): JsonResponse
    {
        try {
            $_event = Event::with(['eventCategories', 'eventThirdParties.eventCategories'])
                ->where('ref', $event)
                ->firstOrFail();

            try {
                $_eventThirdParty = $_event->eventThirdParties()
                    ->where('ref', $eventThirdParty->ref)
                    ->firstOrFail();

            } catch (ModelNotFoundException $e) {
                return $this->error('The event partner was not found!', 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('Edit an event partner!', 200, [
            'event' => new EventResource($_event),
            'event_third_party' => new EventThirdPartyResource($_eventThirdParty)
        ]);
    }

    /**
     * Update an event's partner (third party integration)
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam event_third_party_ref string required The ref of the event third party. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  EventThirdPartyUpdateRequest   $request
     * @param  Event                          $event
     * @param  EventThirdParty                $eventThirdParty
     * @return JsonResponse
     */
    public function updateThirdParty(EventThirdPartyUpdateRequest $request, Event $event, EventThirdParty $eventThirdParty): JsonResponse
    {
        try {
            $_event = Event::where('ref', $event->ref)
                ->firstOrFail();

            try {
                $_eventThirdParty = $_event->eventThirdParties()
                    ->where('ref', $eventThirdParty->ref)
                    ->firstOrFail();

                    $_eventThirdParty = $this->updateEventThirdParty($_eventThirdParty, $event, (array) $request->all());

                // try {
                //     if ($request->filled('partner_channel')) {
                //         $partner = Partner::whereHas('partnerChannels', function ($query) use ($request) { // Get the partner to which the partner channel belongs
                //             $query->where('ref', $request->partner_channel);
                //         })->first();

                //         $__eventThirdParty = EventThirdParty::where('event_id', $_event->id) // Ensure only one partner channel can be added per partner for a given event
                //             ->whereHas('partnerChannel', function ($query) use ($partner, $eventThirdParty) {
                //                 $query->whereHas('partner', function ($query) use ($partner, $eventThirdParty) {
                //                     $query->where('id', $partner?->id)
                //                         ->whereNot('id', $eventThirdParty->partnerChannel?->partner?->id); // Ignore the partner associated with the event third party being edited when checking for uniqueness
                //                 });
                //             });

                //         if ($__eventThirdParty->exists()) {
                //             throw new ModelNotFoundException('A channel belonging to this partner already exist! Only one partner channel can be added per partner for a given event. Please update the existing one.');
                //         }

                //         $_eventThirdParty->partner_channel_id = PartnerChannel::where('ref', $request->partner_channel)->value('id');
                //     }
                    // try {
                    //     $_eventThirdParty->fill($request->only(['external_id', 'occurrence_id']));
                    //     $_eventThirdParty->save();

                //         if ($request->filled('categories')) { // Save event categories and their equivalences on the third party platforms
                //             $thirdPartyCategoriesEquivalence = [];

                //             foreach ($request->categories as $category) {
                //                 $eventCategoryId = EventCategory::where('ref', $category['ref'])->value('id');
                //                 unset($category['ref']);

                //                 $thirdPartyCategoriesEquivalence[$eventCategoryId] = $category;
                //             }

                //             $_eventThirdParty->eventCategories()->sync($thirdPartyCategoriesEquivalence);
                //         }

                //         CacheDataManager::flushAllCachedServiceListings($this->eventService);
                //         CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                //         CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                //         (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
                //     }  catch (QueryException $e) {
                //         return $this->error('Unable to update the event partner! Please try again.', 406, $e->getMessage());
                //     }
                // } catch (ModelNotFoundException $e) {
                //     return $this->error($e->getMessage(), 404);
                // }
            } catch (ModelNotFoundException $e) {
                return $this->error('The event partner was not found!', 404);
            } catch (Exception $e) {
                return $this->error($e->getMessage(), 404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('Successfully updated the event partner!', 200, new EventThirdPartyResource($_eventThirdParty->load(['eventCategories', 'partnerChannel.partner.upload'])->refresh()));
    }

    /**
     * Delete one or many event partners (third party integration)
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     *
     * @param  EventThirdPartyDeleteRequest $request
     * @param  string                       $event
     * @return JsonResponse
     */
    public function destroyThirdParty(EventThirdPartyDeleteRequest $request, string $event): JsonResponse
    {
        $eventThirdParties = EventThirdParty::whereHas('event', function ($query) use ($event) {
                $query->where('ref', $event)
                    ->whereHas('eventCategories', function ($query) {
                        $query->whereHas('site', function ($query) {
                            $query->hasAccess()
                                ->makingRequest();
                        });
                    });
            });

        try {
            $eventThirdParties = $eventThirdParties->whereIn('ref', $request->refs)
                ->get();

            if (! $eventThirdParties->count()) {
                throw new ModelNotFoundException();
            }

            try {
                DB::beginTransaction();

                foreach ($eventThirdParties as $thirdParty) {
                    $thirdParty->delete();
                }

                DB::commit();

                CacheDataManager::flushAllCachedServiceListings($this->eventService);
                CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
            } catch(QueryException $e) {
                DB::rollback();
                return $this->error('Unable to delete the '. static::singularOrPlural(['event partner', 'event partners'], $request->refs) .'! Please try again.', 406, $e->getMessage());
            }
        } catch(ModelNotFoundException $e) {
            return $this->error('The '. static::singularOrPlural(['event partner was', 'event partners were'], $request->refs) .' not found!', 404);
        }

        return $this->success('Successfully deleted the '. static::singularOrPlural(['event partner', 'event partners'], $request->refs), 200, new EventThirdPartyResource($eventThirdParties));
    }

    /**
     * Delete One/Many FAQs
     *
     * Delete multiple FAQs from an event.
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @bodyParam faqs_ids array required The ids of the FAQs to be deleted. Example: [1, 2, 3]
     *
     * @param  DeleteEventFaqsRequest $request
     * @param  Event                  $event
     * @return JsonResponse
     */
    public function destroyManyFaqs(DeleteEventFaqsRequest $request, Event $event): JsonResponse
    {
        try {
            $this->faqRepository->destroyManyFaqs($request->validated(), $event);

            CacheDataManager::flushAllCachedServiceListings($this->eventService);
            CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
            (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
        } catch (\Exception $exception) {
            return $this->error('An error occured while deleting specified event FAQ(s). Please try again.', 400);
        }

        return $this->success('Successfully deleted specified event FAQ(s).', 200, [
            'event' => new EventResource($event->load(['faqs']))
        ]);
    }

    /**
     * Delete One/Many FAQ Details
     *
     * Delete multiple Event FAQ details by specifying their ids.
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @urlParam faq_ref string required The ref of the faq. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @bodyParam faq_details_ids string[] required The list of ids associated with specific event faq_details ids. Example: [1,2]
     *
     * @param  DeleteFaqDetailsRequest $request
     * @param  Event $event
     * @param  Faq $faq
     * @return JsonResponse
     */
    public function destroyManyFaqDetails(DeleteFaqDetailsRequest $request, Event $event, Faq $faq): JsonResponse
    {
        try {
            $this->faqRepository->destroyManyFaqDetails($request->validated(), $faq);

            CacheDataManager::flushAllCachedServiceListings($this->eventService);
            CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
            (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
        } catch (\Exception $exception) {
            return $this->error('An error occured while deleting specified event FAQ detail(s). Please try again.', 400);
        }

        return $this->success('Successfully deleted specified event FAQ detail(s).', 200, [
            'event' => new EventResource($event->load(['faqs']))
        ]);
    }

    /**
     * Remove faq details image
     *
     * @param  Event $event
     * @param  Faq $faq
     * @param  FaqDetails $faqDetails
     * @param  string $upload_ref
     * @return JsonResponse
     */
    public function removeFaqDetailImage(Event $event, Faq $faq, FaqDetails $faqDetails, string $upload_ref)
    {
        try {
            $this->faqRepository->removeImage($faqDetails, $upload_ref);

            CacheDataManager::flushAllCachedServiceListings($this->eventService);
            CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
            CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
            (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
        } catch (ModelNotFoundException $e) {
            return $this->error('The image was not found!', 404);
        }

        return $this->success('Successfully removed the image!', 200, [
            'event' =>  $event->load(['faqs'])
        ]);
    }

    /**
     * Get the event's medals.
     *
     * @urlParam event_ref string required The ref of the event. Example: 97ad9df6-bc08-4729-b95e-3671dc6192c2
     * @queryParam term string Filter by term. The term to search for. No-example
     *
     * @param  Request $request
     * @param  string  $event
     * @return void
     */
    public function medals(Request $request, string $event)
    {
        $validator = Validator::make($request->all(), [
            'term' => ['sometimes', 'nullable', 'string']
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422,  $validator->errors()->messages());
        }

        try {
            $event = Event::with(['medals' => function ($query) {
                $query->with(['site', 'upload'])
                    ->when(request()->filled('term'), function($query) {
                        $query->where('name', 'like', '%'.request()->term.'%');
                    })->withTrashed();
            }])->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                });
            })->where('ref', $event)->firstOrFail();

        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success('Successfully retrieved event medals.', 200, [
            'medals' => new MedalResource($event->medals),
            'query_params' => $request->all(),
            'action_messages' => Medal::$actionMessages
        ]);
    }

    /**
     * The upcoming events.
     *
     * @queryParam page integer The page data to return Example: 1
     * @queryParam per_page integer Items per page No-example
     *
     * @param  Request       $request
     * @return JsonResponse
     */
    public function upcoming(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'numeric', 'integer', 'min:1']
        ]);

        if ($validator->fails()) {
            return $this->error('Please resolve the warnings!', 422,  $validator->errors()->messages());
        }

        try {
            $events = Event::select(['id', 'ref', 'name', 'slug'])
                ->with(['eventCategories' => function ($query) {
                    $query->whereHas('site', function ($query) {
                        $query->makingRequest();
                    });
                }, 'image'])
            ->state(EventStateEnum::Live);

            if (! AccountType::isAdmin()) {
                $events = $events->partnerEvent(Event::ACTIVE);
            }

            $events = $events->whereHas('eventCategories', function ($query) {
                $query->whereHas('site', function ($query) {
                    $query->makingRequest();
                })->where('start_date', '>', Carbon::now());
            });

            $events = $events->orderBy(
                EventEventCategory::select('start_date')
                    ->whereColumn('event_id', 'events.id')
                    ->orderBy('start_date')
                    ->limit(1)
                )->when(
                    $request->filled('per_page'),
                    fn ($query) => $query->paginate($request->per_page),
                    fn ($query) => $query->paginate()
                );
        } catch (ModelNotFoundException $e) {
            return $this->error('The events were not found!', 404);
        }

        return $this->success('The list of upcoming events', 200, [
            'events' => new EventResource($events)
        ]);
    }

    /**
     * Save event third party configurations
     * 
     * @param  Event  $event
     * @param  array  $eventThirdParty
     * @return EventThirdParty
     */
    private function saveEventThirdParty(Event $event, array $eventThirdParty): EventThirdParty
    {
        $_eventThirdParty = new EventThirdParty();
        $_eventThirdParty->event_id = $event->id;
        $_eventThirdParty->partner_channel_id = PartnerChannel::where('ref', $eventThirdParty['partner_channel'])->value('id');
        $_eventThirdParty->external_id = $eventThirdParty['external_id'];
        $_eventThirdParty->occurrence_id = $eventThirdParty['occurrence_id'] ?? null;
        $_eventThirdParty->save();

        // Save event categories and their equivalences on the third party platforms
        $thirdPartyCategoriesEquivalence = [];

        foreach ($eventThirdParty['categories'] as $category) {
            $eventCategoryId = EventCategory::where('ref', $category['ref'])->value('id');
            unset($category['ref']);

            $thirdPartyCategoriesEquivalence[$eventCategoryId] = $category;
        }

        $_eventThirdParty->eventCategories()->sync($thirdPartyCategoriesEquivalence);

        return $_eventThirdParty;
    }

    /**
     * Update event third party configurations
     * 
     * @param  EventThirdParty  $eventThirdParty
     * @param  Event            $event
     * @param  array            $_eventThirdParty
     * @return EventThirdParty
     */
    private function updateEventThirdParty(EventThirdParty $eventThirdParty, Event $event, array $_eventThirdParty): EventThirdParty
    {
        try {
            if (isset($_eventThirdParty['partner_channel'])) {
                $partner = Partner::whereHas('partnerChannels', function ($query) use ($_eventThirdParty) { // Get the partner to which the partner channel belongs
                    $query->where('ref', $_eventThirdParty['partner_channel']);
                })->first();

                $__eventThirdParty = EventThirdParty::where('event_id', $event->id) // Ensure only one partner channel can be added per partner for a given event
                    ->whereHas('partnerChannel', function ($query) use ($partner, $eventThirdParty) {
                        $query->whereHas('partner', function ($query) use ($partner, $eventThirdParty) {
                            $query->where('id', $partner?->id)
                                ->whereNot('id', $eventThirdParty->partnerChannel?->partner?->id); // Ignore the partner associated with the event third party being edited when checking for uniqueness
                        });
                    });

                if ($__eventThirdParty->exists()) {
                    throw new ModelNotFoundException('A channel belonging to this partner already exist! Only one partner channel can be added per partner for a given event. Please update the existing one.');
                }

                $eventThirdParty->partner_channel_id = PartnerChannel::where('ref', $_eventThirdParty['partner_channel'])->value('id');
            }

            try {
                $eventThirdParty->external_id = $_eventThirdParty['external_id'] ?? $eventThirdParty->external_id;
                $eventThirdParty->occurrence_id = $_eventThirdParty['occurrence_id'] ?? (isset($_eventThirdParty['occurrence_id_not_set_on_third_party']) && $_eventThirdParty['occurrence_id_not_set_on_third_party'] ? null : $eventThirdParty->occurrence_id);
                $eventThirdParty->save();

                if ($_eventThirdParty['categories']) { // Save event categories and their equivalences on the third party platforms
                    $thirdPartyCategoriesEquivalence = [];

                    foreach ($_eventThirdParty['categories'] as $category) {
                        $eventCategoryId = EventCategory::where('ref', $category['ref'])->value('id');
                        unset($category['ref']);

                        $thirdPartyCategoriesEquivalence[$eventCategoryId] = $category;
                    }

                    $eventThirdParty->eventCategories()->sync($thirdPartyCategoriesEquivalence);
                }

                CacheDataManager::flushAllCachedServiceListings($this->eventService);
                CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);
                CacheDataManager::flushAllCachedServiceListings(new GlobalSearchDataService);
                (new CacheDataManager(new PartnerEventDataService(), 'getPaginatedList'))->flushCachedServiceListings();
            }  catch (QueryException $e) {
                throw new Exception('Unable to update the event partner! Please try again.');
            }
        } catch (ModelNotFoundException $e) {
            throw new Exception($e->getMessage());
        }

        return $eventThirdParty;
    }
}
