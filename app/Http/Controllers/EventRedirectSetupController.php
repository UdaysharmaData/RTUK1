<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRedirectRequest;
use App\Modules\Event\Models\Event;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\EventDataService;
use App\Services\DataServices\RedirectDataService;
use App\Traits\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class EventRedirectSetupController extends Controller
{
    use Response;

    public function __construct(protected RedirectDataService $redirectDataService)
    {
        parent::__construct();
    }

    /**
     * Setup Event Redirect.
     *
     * Add a new redirect to the system.
     *
     * @group Redirects
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam target_url string required The target url for the redirect. Example: https://google.com
     * @bodyParam redirect_url string required The redirect url for the redirect. Example: https://google.com
     * @bodyParam status string required The status for the redirect. Example: temporal,permanent
     * @bodyParam type string required The type for the redirect. Example: single,collection
     * @bodyParam model object required The object representation of the entity being redirected. Example: {"name": "Event", "ref": "event_1"}
     *
     * @param StoreRedirectRequest $request
     * @param string $ref
     * @return JsonResponse
     */
    public function __invoke(StoreRedirectRequest $request, string $ref): \Illuminate\Http\JsonResponse
    {
        try {
            $event = Event::withTrashed()->where('ref', $ref)->first();
            $resource = $this->redirectDataService->addRedirect($event, $request->validated());
            CacheDataManager::flushAllCachedServiceListings(new EventDataService());

            return $this->success('A redirect link has been setup for this resource.', 201, [
                'event' => $event->load('redirect')
            ]);
        } catch (ModelNotFoundException $e) {
            if ($request->has('model')) {
                $resource = $this->redirectDataService->addRedirectToDeletedEntity(Event::class, $request->validated());

                return $this->success('A redirect link has been setup for this deleted resource.', 201);
            } else {
                return $this->error('The resource you are trying to redirect does not exist.', 404);
            }
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error('An error occurred while setting up a redirect link for this resource.', 400);
        }
    }
}
