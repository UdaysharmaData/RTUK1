<?php

namespace App\Http\Controllers;

use App\Traits\Response;
use App\Models\Experience;
use App\Modules\Event\Models\Event;
use App\Services\DataCaching\CacheDataManager;
use App\Http\Requests\StoreEventExperienceRequest;
use App\Http\Requests\UpdateEventExperienceRequest;
use App\Services\DataServices\EventClientDataService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventExperienceController extends Controller
{
    use Response;

    /**
     * Get Event Experiences
     *
     * Endpoint lists available experiences for a specified event.
     *
     * @group Experience
     * @authenticated
     * @header Content-Type application/json
     *
     * @urlParam event_ref string required The ref attribute of the event. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param Event $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Event $event): \Illuminate\Http\JsonResponse
    {
        try {
            return $this->success('Event experiences', 200, [
                'event_experiences' => $event->experiences,
                'all_experiences' => Experience::whereHas('site', function ($query) {
                    $query->makingRequest();
                })->latest()->paginate(10),
            ]);
        } catch (ModelNotFoundException|NotFoundHttpException $exception) {
            return $this->error('We could not find the event specified.', 400);
        }
    }

    /**
     * Add/Update Event Experience
     *
     * Create a new event experience.
     *
     * @group Experience
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam experience string required Specify name attribute of experience being associated to event. Example: Atmosphere
     * @bodyParam value string required Specify an experience value (from list of available values on selected experience above). Example: Amazing
     * @bodyParam description string required Provide a description
     * @urlParam event_ref string required The ref attribute of the event. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param StoreEventExperienceRequest $request
     * @param Event $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEventExperienceRequest $request, Event $event): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $experienceId = Experience::where('name', $validated['experience'])->firstOrFail()?->id;
            $event->experiences()->syncWithoutDetaching([
                $experienceId => [
                    'value' => $validated['value'],
                    'description' => $validated['description']
                ]
            ]);

            CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);

            return $this->success('Successfully added experience!', 200, [
                'event_experiences' => $event->experiences
            ]);
        } catch (ModelNotFoundException|NotFoundHttpException $exception) {
            return $this->error('We could not find the event/experience you were trying to update.', 400);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to update event experience', 400);
        }
    }

    /**
     * Remove Event Experience(s)
     *
     * Remove experiences from a single event.
     *
     * @group Experience
     * @authenticated
     * @header Content-Type application/json
     *
     * @bodyParam experiences[] string required Specify ids for experiences to be removed from event. [1, 2, 3]
     * @urlParam event_ref string required The ref attribute of the event. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param UpdateEventExperienceRequest $request
     * @param Event $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateEventExperienceRequest $request, Event $event): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $event->experiences()->detach($validated['experiences']);
            
            CacheDataManager::flushAllCachedServiceListings(new EventClientDataService);

            return $this->success('Successfully removes experience(s) from event!', 200, [
                'event_experiences' => $event->experiences
            ]);
        } catch (ModelNotFoundException|NotFoundHttpException $exception) {
            return $this->error('We could not find the event/experience you were trying to update.', 400);
        } catch (\Exception $exception) {
            return $this->error('An error occurred while trying to remove event experience', 400);
        }
    }
}
