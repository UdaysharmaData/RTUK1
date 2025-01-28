<?php

namespace App\Mail\event;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\Event\Models\Event;

class EventsArchivedMail extends MailLayout
{
    private array $result;

    public function __construct($result, $site = null)
    {
        $this->result = $result;

        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            subject: 'Archived Events: Review Needed'
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        $header = [
            ['value' => '#'],
            ['value' => 'Archived Event'],
            ['value' => 'New Event'],
        ];

        $events = [];
        $newEventsIds = [];

        foreach ($this->result as $key => $result) {
            $events[] = [
               ['value' => $key + 1, 'className' => 'text__bold px-10'],
               ['value' => "<a href=" . $this->mailHelper->portalLink('event/' . $result->event->ref . '/edit') . ">" . $result->event->formattedName . "</a>", 'className' => 'text__center'],
               ['value' => "<a href=" . $this->mailHelper->portalLink('event/' . $result->clone->ref . '/edit') . ">" . $result->clone->formattedName . "</a>", 'className' => 'text__center'],
           ];

           $newEventsIds[] = $result->clone->id;
        }

        return new Content(
            view: 'mails.event.archiveds',
            markdown: 'mails.event.archiveds',
            with: [
                'user' => [
                  'name' =>  $this->to[0]['name'],
                ],
                'member' => $this->mailHelper->developerMember(),
                'header' => $header,
                'events' => $events,
                'new_events_ids' => $newEventsIds
            ]
        );
    }
}
