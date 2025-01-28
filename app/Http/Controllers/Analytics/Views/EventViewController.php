<?php

namespace App\Http\Controllers\Analytics\Views;

use App\Http\Controllers\Analytics\AnalyticsController;
use App\Modules\Event\Models\Event;
use App\Services\Analytics\Events\AnalyticsViewEvent;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;

class EventViewController extends AnalyticsController
{
    use Response;

    public function __construct()
    {
        parent::__construct('views');
    }

    /**
     * Capture an Event view
     *
     * @group Analytics
     * @authenticated
     * @header Content-Type application/json
     * @header X-Platform-User-Identifier-Key RTHUB.v1.98591b54-db61-46d4-9d29-47a8a7f325a8.1675084780
     *
     * @urlParam event_ref string required The ref attribute of the event. Example: 9762db71-f5a6-41c4-913e-90b8aebad733
     *
     * @param Event $event
     * @return JsonResponse
     */
    public function __invoke(Event $event): JsonResponse
    {
        AnalyticsViewEvent::dispatch($event);

        return $this->success('Event view registered.', 200, [
            'event' => $event->fresh()
        ]);
    }
}
