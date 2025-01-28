<?php

namespace App\Mail\event;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use App\Mail\MailLayout;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventEventCategory;

class TotalPlacesExhaustedMail extends MailLayout
{
    use Queueable, SerializesModels;

    private EventEventCategory $eec;

    private Charity|null $charity;

    private object $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(EventEventCategory $eec, $user, $charity = null)
    {
        $this->eec = $eec;
        $this->user = $user;
        $this->charity = $charity;

        parent::__construct();
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            subject: 'Event: Available Places Exhausted',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.event.total-places-exhausted',
            markdown: 'mails.event.total-places-exhausted',
            with: [
                'name' => $this->to[0]['name'],
                'title' => 'Event Total Places Exhausted',
                'event' => [
                    'ref' => $this->eec->event?->ref,
                    'name' => $this->eec->event?->name,
                ],
                'user' => [
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'email' => $this->user->email,
                ],
                'member' => $this->mailHelper->developerMember(),
                'charity' => $this->charity ? [
                    'name' => $this->charity->name
                ] : null
            ]
        );
    }
}
