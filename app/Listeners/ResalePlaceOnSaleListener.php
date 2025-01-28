<?php

namespace App\Listeners;

use App\Mail\Mail;
use App\Events\ResaleEventOnSaleEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Modules\Charity\Models\ResaleNotification;
use App\Mail\market\resalePlace\NewResalePlaceMail;

class ResalePlaceOnSaleListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ResaleEventOnSaleEvent  $event
     * @return void
     */
    public function handle(ResaleEventOnSaleEvent $event)
    {
        foreach (ResaleNotification::all() as $resaleNotification) {
            Mail::site()->send(new NewResalePlaceMail($resaleNotification->charity, $event->resalePlace));
        }
        // TODO: Complete this section
    }
}
