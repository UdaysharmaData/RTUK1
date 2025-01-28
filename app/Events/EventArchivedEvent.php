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

class EventArchivedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Variables declaration
     *
     * @var Event       The archieved event
     * @var Event       The new/current event
     * @var array|null
     */
    public $event;
    public $clone;

    /**
     * Create a new event instance.
     *
     * @param  Event $event
     * @param  Event $clone
     * @return void
     */
    public function __construct(Event $event, Event $clone)
    {
        $this->event = $event;
        $this->clone = $clone;
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
