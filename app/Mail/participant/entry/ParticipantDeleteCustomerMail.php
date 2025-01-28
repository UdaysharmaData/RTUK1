<?php

namespace App\Mail\participant\entry;

use App\Http\Helpers\FormatNumber;
use App\Mail\MailLayout;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Participant\Models\Participant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ParticipantDeleteCustomerMail extends MailLayout
{
    private Participant $participant;

    private Transaction|null $refundTransaction;

    private bool $isParticipantCurrentUser;

    public function __construct(Participant $participant, Transaction|null $refundTransaction = null, $isParticipantCurrentUser = false)
    {
        parent::__construct();


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
            to: $this->participant->user?->email,
            subject: 'Your Entry Has Been Withdrawn'
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
            $data['refund']['via_wallet'] = $this->refundTransaction->internalTransactions()->exists();
        }

        return new Content(
            view: 'mails.participant.entry.delete-customer',
            markdown: 'mails.participant.entry.delete-customer',
            with: [
                'user' => [
                    'name' => $this->participant->user?->salutation_name
                ],
                'charity' => [
                    'name' => $this->participant->charity?->name
                ],
                'event' => [
                    'name' => $this->participant->eventEventCategory?->event?->name,
                    'category' => $this->participant?->eventEventCategory?->eventCategory?->name,
                ],
                'isParticipantCurrentUser' => $this->isParticipantCurrentUser,
                'member' => $this->mailHelper->topExecutiveMember(),
                ...$data
            ]
        );
    }
}
