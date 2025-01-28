<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\PartnerCharityAttemptedRegistrationEvent;

class PartnerCharityAttemptedRegistrationListener
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
     * @param  PartnerCharityAttemptedRegistrationEvent  $event
     * @return void
     */
    public function handle(PartnerCharityAttemptedRegistrationEvent $event)
    {
        // TODO: Complete this section. Notify the charity via email
    }
}
