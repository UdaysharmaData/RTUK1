<?php

namespace App\Mail\participant\entry;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\Participant\Models\Participant;

class ParticipantUncompletedRegistration extends MailLayout
{
    private Participant $participant;

    public function __construct(Participant $participant, $site = null)
    {
        $this->participant = $participant->load(['charity', 'user', 'event']);

        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->participant->user?->email,
            subject: 'Complete Your Registration'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.participant.entry.uncompleted',
            markdown: 'mails.participant.entry.uncompleted',
            with: [
                'user' => [
                    'name' => $this->participant->user?->salutation_name
                ],
                'event' => [
                    'ref' => $this->participant->event?->ref,
                    'name' => $this->participant->event?->name
                ],
                'charity' => $this->participant->charity ? [
                    'fundraising_platform_url' => $this->participant->charity?->fundraising_platform_url,
                    'fundraising_ideas_url' => $this->participant->charity?->fundraising_ideas_url
                ] : null,
                'member' => $this->participant->charity ?
                    $this->mailHelper->charityMember($this->participant->charity) :
                    $this->mailHelper->topExecutiveMember()
            ]
        );
    }
}
