<?php

namespace App\Listeners;

use App\Events\EventArchivedEvent;
use App\Traits\AdministratorEmails;
use App\Mail\event\EventArchivedMail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class EventArchivedListener
{
    use AdministratorEmails;

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
     * @param  EventArchivedEvent  $event
     * @return void
     */
    public function handle(EventArchivedEvent $event)
    {
        static::sendEmails(new EventArchivedMail($event->event, $event->clone));
    }
}
