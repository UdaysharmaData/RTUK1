<?php

namespace App\Listeners;

use App\Events\EventsArchivedEvent;
use App\Traits\AdministratorEmails;
use App\Mail\event\EventsArchivedMail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class EventsArchivedListener
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
     * @param  EventsArchivedEvent  $event
     * @return void
     */
    public function handle(EventsArchivedEvent $event)
    {
        static::sendEmails(new EventsArchivedMail($event->result));
    }
}
