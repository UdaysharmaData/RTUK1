<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\Modules\Charity\Models\ResalePlace;

class ResaleEventOnSaleEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Variables declaration
     *
     * @var Charity
     * @var int
     */
    public $resalePlace;
    public $places;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ResalePlace $resalePlace, int $places)
    {
        $this->resalePlace = $resalePlace;
        $this->places = $places;
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
