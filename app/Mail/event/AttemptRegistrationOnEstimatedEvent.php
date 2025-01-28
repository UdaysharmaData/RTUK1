<?php

namespace App\Mail\event;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\Event\Models\Event;

class AttemptRegistrationOnEstimatedEvent extends MailLayout
{
    private Event $event;

    private object $user;

    public function __construct(Event $event, $user, $site = null)
    {
        $this->event = $event;
        $this->user = $user;

        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            subject: 'Attempt Event Registration'
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.event.registration-attempt',
            markdown: 'mails.event.registration-attempt',
            with: [
                'name' => $this->to[0]['name'],
                'event' => [
                    'name' => $this->event->name,
                    'ref' => $this->event->ref
                ],
                'user' => [
                    'email' => $this->user->email,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name
                ],
                'member' => $this->mailHelper->developerMember(),
            ]
        );
    }
}
