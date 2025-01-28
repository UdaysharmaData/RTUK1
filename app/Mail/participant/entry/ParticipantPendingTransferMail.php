<?php

namespace App\Mail\participant\entry;

use App\Http\Helpers\FormatNumber;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\Event\Models\EventEventCategory;
use App\Modules\Participant\Models\Participant;

class ParticipantPendingTransferMail extends MailLayout
{
    private Participant $participant;

    private EventEventCategory $eec;
    private float $total;

    public function __construct(Participant $participant, EventEventCategory $eec, $total, $site = null)
    {
        $this->total = $total;
        $this->participant = $participant->load(['eventEventCategory.event:id,ref,name,slug', 'user']);
        $this->eec = $eec->load(['event:id,name,ref', 'eventCategory:id,name']);

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
            subject: 'A Request To Transfer Your Entry Has Been Initiated'
        );
    }

    /**
     * content
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.participant.entry.pending-transfer',
            markdown: 'mails.participant.entry.pending-transfer',
            with: [
                'user' => [
                    'name' => $this->participant->user?->salutation_name
                ],
                'participant' => [
                    'ref' => $this->participant->ref
                ],
                'event' => [
                    'ref' => $this->participant->eventEventCategory?->event->ref,
                    'slug' => $this->participant->eventEventCategory?->event?->slug,
                    'name' => $this->participant->eventEventCategory?->event?->name,
                    'category' => $this->participant->eventEventCategory?->eventCategory?->name
                ],
                'newEvent' => [
                    'ref' => $this->eec->event?->ref,
                    'eec_ref' => $this->eec->ref,
                    'slug' => $this->participant->event?->slug,
                    'name' => $this->eec->event?->name,
                    'category' => $this->eec->eventCategory?->name
                ],
                'total' => FormatNumber::formatWithCurrency($this->total),
                'member' => $this->mailHelper->topExecutiveMember()
            ]
        );
    }
}