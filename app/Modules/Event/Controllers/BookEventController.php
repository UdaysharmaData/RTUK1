<?php

namespace App\Modules\Event\Controllers;

use App\Modules\Setting\Enums\OrganisationEnum;
use DB;
use Str;
use Auth;
use Rule;
use Excel;
use Storage;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

use App\Modules\Event\Models\Event;
use App\Modules\Participant\Models\Participant;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Event\Models\EventCategoryEventThirdParty;

use App\Modules\Participant\Requests\EntryCreateRequest;

use App\Modules\Event\Resources\EventResource;
use App\Modules\Participant\Resources\ParticipantResource;

use App\Modules\Setting\Enums\SiteEnum;
use App\Enums\PredefinedPartnersEnum;
use App\Enums\ParticipantAddedViaEnum;

use App\Traits\Response;
use App\Traits\SiteTrait;
use App\Traits\UploadTrait;
use App\Traits\DownloadTrait;

/**
 * @group Book Events
 * Manages booked events on the application
 * @authenticated
 */
class BookEventController extends Controller
{
    use Response, SiteTrait, UploadTrait, DownloadTrait;

    /*
    |--------------------------------------------------------------------------
    | Book Event Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles everything that has to do with booked events.
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

        $this->middleware('role:can_manage_registrations', [
            'except' => 'index'
        ]);
    }

    // /**
    //  * The list of booked events
    //  * 
    //  * @urlParam category string required The ref of the category. Example: 97a3ca24-0447-4b49-aa25-a8cddb0e064d
    //  * @queryParam archived boolean Filter by archived.
    //  * @queryParam year int Filter by year. No-example
    //  * @queryParam month int Filter by month. No-example
    //  * @queryParam term string Filter by term. The term to search for. No-example
    //  * @queryParam page integer The page data to return Example: 1 
    //  * @queryParam per_page integer Items per page No-example
    //  * 
    //  * @param  Request          $request
    //  * @param  ?EventCategory   $category
    //  * @return RedirectResponse
    //  */
    // public function index(Request $request, ?EventCategory $category = null): RedirectResponse
    // {
    //     return redirect()->action([PartnerEventController::class, 'index'], [$request, 'category' => $category]);
    // }

    /**
     * Register for an event (participant)
     * Participants register for events through this endpoint.
     * 
     * @urlParam event_ref string required The ref of the event. Example: 97a3ca24-0447-4b49-aa25-a8cddb0e064d
     * @param  string         $event
     * @return JsonResponse
     */
    public function register(string $event): JsonResponse
    {
        try {
            $_event = Event::with(['eventCategories' => function ($query) {
                $query->orderByDesc('registration_deadline');
            }, 'eventThirdParties' => function ($query) {
                    $query->with('eventCategories')
                        ->whereNotNull('external_id')
                        ->whereHas('partnerChannel', function ($query) {
                            $query->whereHas('partner', function ($query) {
                                $query->whereHas('site', function ($query) {
                                    $query->makingRequest();
                                })->where('code', PredefinedPartnersEnum::LetsDoThis->value);
                            });
                        });
                    }, 'image'])
                ->partnerEvent(Event::ACTIVE)
                ->whereHas('eventCategories', function($query) {
                    $query->whereHas('site', function($query) {
                        $query->makingRequest();
                    });
                });

            $_event = $_event->where('ref', $event)->firstOrFail();

        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        return $this->success($message ?? 'Register for an event', 200, [
            'default_charity' => Auth::user()->charityUser?->charity, // TODO: Update this to return RFC default charity for in case the participant does not have default charity and is trying to register through a site that is not RunThrough
            'event' => new EventResource($_event)
        ]);
    }

    /**
     * Store the registered participant.
     * 
     * @param  EntryCreateRequest  $request
     * @param  string              $event
     * @return JsonResponse
     */
    public function store(EntryCreateRequest $request, string $event): JsonResponse
    {
        try {
            // TODO: Update this implementation to handle multiple event registrations.
            //       Create a method that validates the eecs in the request and returns an error (to it's index) if any of them does not have available places or the user has already been registered to (for rfc).
            // $_event = Event::partnerEvent(Event::ACTIVE)
            //     ->whereHas('eventCategories', function($query) {
            //         $query->whereHas('site', function($query) {
            //             $query->makingRequest();
            //         });
            //     });

            // $_event = $_event->where('ref', $event)->firstOrFail();

            // try {
            //     $eec = EventEventCategory::with(['event', 'eventCategory'])
            //         ->where('ref', $request->eec)
            //         ->whereHas('eventCategory', function ($query) {
            //             $query->whereHas('site', function ($query) {
            //                 $query->makingRequest();
            //             });
            //         })->firstOrFail();

            //      if (SiteEnum::belongsToOrganisation(OrganisationEnum::GWActive)) { // In case, by mistake a RunThrough registration is sent to this endpoint, it should be handled appropriately (The endpoint to checkout on LDT is "client/events/ldt/checkout")
            //         $ecetps = EventCategoryEventThirdParty::whereHas('eventThirdParty', function ($query) use ($eec) {
            //             $query->where('event_id', $eec->event_id)
            //                 ->whereNotNull('external_id')
            //                 ->whereHas('partnerChannel', function ($query) {
            //                     $query->whereHas('partner', function ($query) {
            //                         $query->whereHas('site', function ($query) {
            //                             $query->makingRequest();
            //                         })->where('code', PredefinedPartnersEnum::LetsDoThis->value);
            //                     });
            //                 });
            //         })->where('event_category_id', $eec->event_category_id)
            //         ->get();

            //         if ($ecetps->count() > 0) {
            //             $races = $ecetps->map(function ($ecetp) use ($request) {
            //                 $_ecetp = collect($request->ecetps)->firstWhere('ref', $ecetp['ref']);
            //                 $quantity = $_ecetp['quantity'] ?? 0;
        
            //                 return implode(",", array_fill(0, $quantity, $ecetp->external_id));
            //             })->toArray();

            //             $races = implode(",", $races);

            //             return $this->success('Checkout on LDT!', 200, Event::checkoutOnLDT($races, $ecetps[0]->eventThirdParty->external_id));
            //         } else {
            //             return $this->error('The third party was not found!', 404);
            //         }
            //     } else {
            //         try {
            //             $request['email'] = Auth::user()->email;
            //             $request['first_name'] = Auth::user()->first_name;
            //             $request['last_name'] = Auth::user()->last_name;

            //             $register = Participant::registerForEvent($request, $eec, ParticipantAddedViaEnum::BookEvents);

            //             if (! $register->status) { // Register the participant for the event
            //                 throw new \Exception($register->message);
            //             }
            //         } catch (\Exception $e) {
            //             return $this->error('Unable to create the participant! Please try again.', 406, $e->getMessage());
            //         }
            //     }
            // } catch (\Exception $e) {
            //     return $this->error('The event category does not belong to the event!', 404, $e->getMessage());
            // }
        } catch (ModelNotFoundException $e) {
            return $this->error('The event was not found!', 404);
        }

        // return $this->success('Successfully registered for the event!', 200, new ParticipantResource($register->participant->load(['charity', 'eventEventCategory.event', 'eventEventCategory.eventCategory', 'eventPage', 'invoiceItem.invoiceItemable', 'participantCustomFields', 'user.profile.participantProfile'])));
        return $this->success('Successfully registered for the event!', 200);
    }
}
