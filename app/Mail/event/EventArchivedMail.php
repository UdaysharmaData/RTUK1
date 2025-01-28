<?php

namespace App\Mail\event;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\Event\Models\Event;

class EventArchivedMail extends MailLayout
{
    private Event $event;

    private Event $clone;

    public function __construct($event, $clone, $site = null)
    {
        $this->event = $event;
        $this->clone = $clone;

        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            subject: 'Event Archived: Review Needed'
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.event.archived',
            markdown: 'mails.event.archived',
            with: [
                'user' => [
                  'name' =>  $this->to[0]['name'],
                ],
                'member' => $this->mailHelper->developerMember(),
                'event' => [
                    'ref' => $this->event->ref,
                    'name' => $this->event->name
                ],
                'clone' => [
                    'ref' => $this->clone->ref,
                    'name' => $this->clone->name
                ]
            ]
        );
    }
}
