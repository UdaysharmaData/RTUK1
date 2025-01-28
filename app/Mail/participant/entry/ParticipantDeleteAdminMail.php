<?php

namespace App\Mail\participant\entry;

use App\Http\Helpers\FormatNumber;
use App\Mail\MailLayout;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Participant\Models\Participant;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ParticipantDeleteAdminMail extends MailLayout
{
    private Participant $participant;

    private Transaction|null $refundTransaction;

    private bool $isParticipantCurrentUser;


    public function __construct(Participant $participant, Transaction|null $refundTransaction = null ,  $isParticipantCurrentUser = false, $site = null)
    {
        parent::__construct($site);

        $this->participant = $participant;
        $this->refundTransaction = $refundTransaction;
        $this->isParticipantCurrentUser = $isParticipantCurrentUser;
    }

    /**
     * envelope
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            subject: 'Participant Entry Deleted'
        );
    }

    /**
     * content
     *
     * @return Content
     */
    public function content(): Content
    {
        $data['refund'] = null;

        if ($this->refundTransaction) {
            $data['refund']['amount'] = FormatNumber::formatWithCurrency($this->refundTransaction->amount);
        }

        return new Content(
            view: 'mails.participant.entry.delete-admin',
            markdown: 'mails.participant.entry.delete-admin',
            with: [
                'user' => [
                    'name' =>  $this->to[0]['name'],
                ],
                'participant' => [
                    'ref' => $this->participant->ref,
                    'name' => $this->participant?->user?->full_name,
                ],
                'event' => [
                    'name' => $this->participant->eventEventCategory?->event?->name,
                    'category' => $this->participant?->eventEventCategory?->eventCategory?->name,
                ],
                'isParticipantCurrentUser' => $this->isParticipantCurrentUser,
                'member' => $this->mailHelper->developerMember(),
                ...$data
            ]
        );
    }
}
