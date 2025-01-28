<?php

namespace App\Mail\enquiry\campaign;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\Charity\Models\Campaign;

class CampaignCreatedMail extends MailLayout
{
    private Campaign $campaign;

    public function __construct(Campaign $campaign, $site = null)
    {
        $this->campaign = $campaign->load(['charity']);

        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->mailHelper->eventManagers()?->pluck('email'),
            subject: 'New Campaign Created For ' . $this->campaign->charity?->name
        );

    }

    /**
     * @return Content
     */
    public function content(): Content
    {

        return new Content(
            view: 'mails.enquiry.campaign.created',
            markdown: 'mails.enquiry.campaign.created',
            with: [
                'campaign' => [
                    'ref' => $this->campaign->ref
                ],
                'name' => 'Event Manager'
            ]
        );
    }
}
