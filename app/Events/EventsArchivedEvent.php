<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class EventsArchivedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Variables declaration
     *
     * @var array       An array of archived and newly created events
     * @var array|null
     */
    public $result;

    /**
     * Create a new event instance.
     *
     * @param  array $result
     * @return void
     */
    public function __construct(array $result)
    {
        $this->result = $result;
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
