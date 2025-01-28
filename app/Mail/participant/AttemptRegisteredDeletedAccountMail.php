<?php

namespace App\Mail\participant;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\User\Models\User;
use App\Modules\Charity\Models\Charity;
use App\Modules\Event\Models\EventEventCategory;


class AttemptRegisteredDeletedAccountMail extends MailLayout
{

    /**
     * @var User
     */
    private User $user;

    /**
     * @var EventEventCategory
     */
    private EventEventCategory $eec;

    /**
     * @var Charity|mixed|null
     */
    private Charity|null $charity;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, EventEventCategory $eec, $charity = null, $site = null)
    {
        $this->user = $user;
        $this->eec = $eec;
        $this->charity = $charity;

        parent::__construct($site);
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
            subject: 'Participant: Event Registration Failed',
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
            view: 'mails.participant.deleted-account-attempted-registration',
            markdown: 'mails.participant.deleted-account-attempted-registration',
            with: [
                'name' => $this->to[0]['name'],
                'user' => [
                    'ref' => $this->user->ref,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'email' => $this->user->email
                ],
                'member' => $this->mailHelper->developerMember(),
                'charity' => $this->charity ? [
                    'name' => $this->charity->name
                ] : null,
                'event' => [
                    'name' => $this->eec->event?->name,
                ]
            ]
        );
    }
}
