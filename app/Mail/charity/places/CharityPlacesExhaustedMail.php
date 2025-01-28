<?php

namespace App\Mail\charity\places;


use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventEventCategory;

class CharityPlacesExhaustedMail extends MailLayout
{
    private EventEventCategory $eec;

    private Object $user;

    private Charity $charity;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(EventEventCategory $eec, $user, Charity $charity)
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
            to: $this->charity->email ?: $this->charity->charityOwner?->user?->email,
            subject: 'Charity: Available Places Exhausted',

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
            view: 'mails.charity.places.exhausted',
            markdown: 'mails.charity.places.exhausted',
            with: [
                'title' => $this->subject,
                'user' => [
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'email' => $this->user->email
                ],
                'charity' =>  [
                    'name' => $this->charity->name
                ],
                'event' => [
                    'name' => $this->eec->event?->name,
                    'ref' => $this->eec->event?->ref
                ]
            ]
        );
    }
}
