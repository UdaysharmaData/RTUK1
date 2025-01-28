<?php

namespace App\Mail\charity\membership;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;

use App\Mail\MailLayout;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use App\Modules\Charity\Models\Charity;
use App\Enums\CharityMembershipTypeEnum;

class MembershipRenewalMail extends MailLayout
{
    private Charity $charity;

    private Invoice $invoice;

    public function __construct(Charity $charity, Invoice $invoice, $site = null)
    {
        $this->charity = $charity->load('charityManager', 'charityOwner');
        $this->invoice = $invoice;

        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->charity->finance_contact_email ?: $this->charity->charityOwner->user?->email,
            subject: 'Membership Renewal'
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.charity.membership.renewal',
            markdown: 'mails.charity.membership.renewal',
            with: [
                'charity' => [
                    'name' => $this->charity->finance_contact_name ?? $this->charity->name,
                    'membership' => [
                        'type' => $this->charity->latestCharityMembership?->type,
                        'monthly_subscription' => $this->charity->latestCharityMembership->type == CharityMembershipTypeEnum::TwoYear ? 24 : 12
                    ],
                    'invoice' => [
                        'status' => $this->invoice->status,
                        'date' => $this->invoice->date
                    ],
                    'manager' => [
                        'name' => $this->charity->charityManager?->user?->full_name,
                        'email' => $this->charity->charityManager?->user?->email,
                    ]
                ]
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments(): array
    {
        $file = Storage::disk(config('filesystems.default'))->path($this->invoice->upload?->url);
        if (!file_exists($file)) {
            Invoice::generatePdf($this->invoice->load(['invoiceable', 'invoiceItems.invoiceItemable', 'upload']));
            $file = Storage::disk(config('filesystems.default'))->path($this->invoice->upload?->url);
        }

        return [
            Attachment::fromPath($file)
                ->as('invoice.pdf')
                ->withMime('application/pdf')
        ];
    }
}
