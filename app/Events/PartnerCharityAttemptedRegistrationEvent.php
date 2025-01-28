<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\Modules\Event\Models\Event;
use App\Modules\Charity\Models\Charity;

class PartnerCharityAttemptedRegistrationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Variables declaration
     *
     * @var Charity
     * @var Event
     * @var array|null
     */
    public $charity;
    public $event;
    public $request;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Charity $charity, Event $event, ?array $request)
    {
        $this->charity = $charity;
        $this->event = $event;
        $this->request = $request;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
