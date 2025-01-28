<?php

namespace App\Mail\charity;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\Participant\Models\Participant;

class CharityNewParticipantMail extends MailLayout
{
    /**
     * @var Participant
     */
    private Participant $participant;

    public function __construct(Participant $participant, $site = null)
    {
        $this->participant = $participant->load(['charity', 'event']);

        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->participant->charity->email ?: $this->participant->charity->charityOwner?->user?->email,
            subject: "New {$this->participant->event->name} Registration",
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.charity.new-participant',
            markdown: 'mails.charity.new-participant',
            with: [
                'title' => $this->subject,
                'participant' => [
                    'ref' => $this->participant->ref,
                    'latest_action' => $this->participant->latest_action
                ],
                'event' => [
                    'name' => $this->participant->event?->name
                ],
                'charity' => [
                    'name' => $this->participant->charity?->name
                ],
                'member' => $this->mailHelper->charityManagerMember($this->participant->charity)
            ]
        );
    }
}
