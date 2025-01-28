<?php

namespace App\Mail\charity\membership;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

use App\Mail\MailLayout;
use App\Modules\Charity\Models\Charity;


class MembershipExpiredMail extends MailLayout
{

    private Charity $charity;

    public function __construct(Charity $charity, $site = null)
    {
        $this->charity = $charity;

        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->charity->email ?: $this->charity->charityOwner?->user?->email,
            subject: 'Membership Expired'
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.charity.membership.expired',
            markdown: 'mails.charity.membership.expired',
            with: [
                'charity' => [
                    'name' => $this->charity['name']
                ],
                'member' => $this->mailHelper->charityManagerMember($this->charity)
            ]
        );
    }

}
