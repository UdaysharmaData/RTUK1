<?php

namespace App\Mail\participant\entry;

use App\Http\Helpers\FormatNumber;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\Finance\Enums\TransactionTypeEnum;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Participant\Models\Participant;
// use Illuminate\Contracts\Queue\ShouldQueue;

class ParticipantFailedTransferMail extends MailLayout // implements ShouldQueue
{
    private Participant $participant;

    private Transaction $transaction;

    public function __construct(Participant $participant, Transaction $transaction, $site = null)
    {
        $this->participant = $participant->load(['user']);

        $this->transaction = $transaction;

        parent::__construct($site);
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
            subject: 'Your Entry Could Not Be Transferred'
        );
    }

    /**
     * content
     *
     * @return Content
     */
    public function content(): Content
    {
        $data = [];

        $transaction = $this->transaction;

        if ($transaction->type == TransactionTypeEnum::Refund) {
            $data['refundedAmount'] = FormatNumber::formatWithCurrency($transaction->amount);
        }

        return new Content(
            view: 'mails.participant.entry.failed-transfer',
            markdown: 'mails.participant.entry.failed-transfer',
            with: [
                'user' => [
                    'name' => $this->participant->user?->salutation_name
                ],
                'participant' => [
                    'ref' => $this->participant->ref
                ],
                'oldEec' => $transaction->externalTransaction->payload['refund']['old_eec'],
                'newEec' => $transaction->externalTransaction->payload['refund']['new_eec'],
                'member' => $this->mailHelper->topExecutiveMember(),
                ...$data
            ]
        );
    }
}
