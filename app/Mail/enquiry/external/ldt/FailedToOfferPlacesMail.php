<?php

namespace App\Mail\enquiry\external\ldt;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;

class FailedToOfferPlacesMail extends MailLayout
{
    /**
     * @var int
     */
    private int $numberOfParticipants;


    public function __construct($numberOfParticipants, $site = null,)
    {
        $this->numberOfParticipants = $numberOfParticipants;

        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            subject: 'External Enquiries: Unable to Offer Places'
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.enquiry.external.ldt.failed-to-offer-places',
            markdown: 'mails.enquiry.external.ldt.failed-to-offer-places',
            with: [
                'name' => $this->to[0]['name'],
                'numberOfParticipants' => $this->numberOfParticipants,
                'member' => $this->mailHelper->developerMember()
            ]
        );
    }
}
