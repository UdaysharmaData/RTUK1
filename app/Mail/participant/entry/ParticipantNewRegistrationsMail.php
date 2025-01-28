<?php

namespace App\Mail\participant\entry;

use App\Modules\Setting\Enums\OrganisationEnum;
use \Illuminate\Support\Str;
use App\Http\Helpers\FormatNumber;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Mail\Mailables\Attachment;

use App\Mail\MailLayout;

use App\Modules\Setting\Enums\SiteEnum;
use App\Modules\Finance\Enums\TransactionTypeEnum;

use App\Models\Invoice;
use App\Modules\User\Models\User;
use App\Modules\Setting\Models\Site;
use App\Modules\Charity\Models\Charity;
use App\Modules\Enquiry\Models\Enquiry;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Enquiry\Models\ExternalEnquiry;
use App\Modules\Participant\Models\ParticipantExtra;

class ParticipantNewRegistrationsMail extends MailLayout
{
    private string|null $user_exception_email;

    private User|string|null $user;

    private Invoice|null $invoice;

    private Transaction|null $refundTransaction;
 
    private ExternalEnquiry|Enquiry|null $externalEnquiry;

    private ParticipantExtra|null $participantExtra;

    private array $extraData;

    private $invoicePdfFile;

    /**
     * @param User|string|null               $user
     * @param array                          $extraData
     * @param Invoice|Transaction|null       $invoiceOrRefundTransaction
     * @param Site|null                      $site
     * @param ExternalEnquiry|Enquiry|null   $externalEnquiry
     * @param ParticipantExtra|null          $participantExtra
     */
    public function __construct(User|string|null $user = null, array $extraData, Invoice|Transaction|null $invoiceOrRefundTransaction = null, ?Site $site = null, ExternalEnquiry|Enquiry|null $externalEnquiry = null, ?ParticipantExtra $participantExtra = null)
    {
        $this->user_exception_email = is_string($user) ? $user : null;
        $this->user = $this->user_exception_email ? null : $user;
        $this->invoice = $invoiceOrRefundTransaction instanceof Invoice ? $invoiceOrRefundTransaction?->load(['invoiceItems', 'upload', 'transactions']) : null;
        $this->refundTransaction = $invoiceOrRefundTransaction instanceof Transaction ? $invoiceOrRefundTransaction->load('externalTransaction') : null;
        $this->extraData = $extraData;
        $this->externalEnquiry = $externalEnquiry;
        $this->participantExtra = $participantExtra;

        if ($this->invoice && SiteEnum::belongsToOrganisation(OrganisationEnum::GWActive)) { // Invoices are not to be sent to participants for the given sites
            $this->invoicePdfFile = null;
        } else {
            // $this->invoicePdfFile = $this->getInvoicePdfFile();
        }

        parent::__construct($site);
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->user_exception_email ?? $this->user->email,
            subject: 'New Event Registrations'
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        $data = [];

        if ($this->invoice || $this->refundTransaction) { // Use the refund transaction when payment & refund transactions were not assigned to an entity as it couldn't be linked to a user.
            if ($this->invoice) {
                $transactions = $this->invoice?->transactions;
                $refundTrans = $transactions ? $transactions->firstWhere('type', TransactionTypeEnum::Refund) : null;
            } else if ($this->refundTransaction) {
                $refundTrans = $this->refundTransaction;
            }

            if ($refundTrans) {
                $data['refundedAmount'] = FormatNumber::formatWithCurrency($refundTrans->amount);
                $refunds = $refundTrans->externalTransaction?->payload['refund'];
            }
        }

        $header = [];

        $header = [
            ['value' => '#'],
            ['value' => 'Event'],
            ['value' => 'Category', 'className' => 'text__center'],
            ['value' => 'Quantity', 'className' => 'text__center']
        ];

        if (!$this->externalEnquiry) {
            $header = [
                ...$header,
                ['value' => 'Price', 'className' => 'text__center'],
                ['value' => 'Total', 'className' => 'text__center']
            ];
        }

        if (isset($this->extraData['passed']) && isset($this->extraData['passed']['eecs']) && !empty($this->extraData['passed']['eecs'])) {
            if (isset($this->extraData['passed']['eecs'][0]['participant']) && isset($this->extraData['passed']['eecs'][0]['participant']['charity_id'])) {
                $charity = Charity::find($this->extraData['passed']['eecs'][0]['participant']['charity_id']);
            }

            $data['passed'] = [];
            $total = 0;

            foreach ($this->extraData['passed']['eecs'] as $key => $eec) {
                $passed = [
                    ['value' => $key + 1, 'className' => 'text__bold px-10'],
                    ['value' => "<div class='item'>
                                    {$eec['name']}.<br />
                                </div>"],
                    ['value' => $eec['category'], 'className' => 'text__center'],
                    ['value' => 1, 'className' => 'text__center']
                ];

                if (!$this->externalEnquiry) {
                    $passed = [
                        ...$passed,
                        ['value' => FormatNumber::formatWithCurrency($eec["registration_fee"]), 'className' => 'text__center'],
                        ['value' => FormatNumber::formatWithCurrency($eec["registration_fee"]), 'className' => 'text__center text__bold']
                    ];
                }

                $data['passed'][] = $passed;
                $total += $eec['registration_fee'];
            }

            if (!$this->externalEnquiry) {
                $data['passed'][] = [
                    ['value' => 'Paid', 'className' => 'text__bold px-10', 'attr' => 'colspan=5'],
                    ['value' => FormatNumber::formatWithCurrency($total), 'className' => 'text__center text__bold']
                ];
            }
        }

        if (isset($this->extraData['failed']) && isset($this->extraData['failed']['eecs']) && !empty($this->extraData['failed']['eecs'])) {
            $data['failed'] = [];
            $total = 0;

            foreach ($this->extraData['failed']['eecs'] as $key => $eec) {
                $message = Str::replace(" (" . $eec['category'] . ")", '', Str::replace($eec['name'], '', $eec['message']));

                $failed = [
                    ['value' => $key + 1, 'className' => 'text__bold px-10'],
                    ['value' => "<div class='item'>
                                    {$eec['name']}.<br />
                                    <span class='f-14'>{$message}</span>
                                </div>"],
                    ['value' => $eec['category'], 'className' => 'text__center'],
                    ['value' => 1, 'className' => 'text__center'],
                ];

                if (!$this->externalEnquiry) {
                    $failed = [
                        ...$failed,
                        ['value' => FormatNumber::formatWithCurrency($eec["registration_fee"]), 'className' => 'text__center'],
                        ['value' => FormatNumber::formatWithCurrency($eec["registration_fee"]), 'className' => 'text__center text__bold']
                    ];
                }

                $data['failed'][] = $failed;
                $total += $eec['registration_fee'];
            }

            $data['failed'][] = [
                ['value' => 'Total', 'className' => 'text__bold px-10', 'attr' => 'colspan=5'],
                ['value' => FormatNumber::formatWithCurrency($total), 'className' => 'text__center text__bold']
            ];
        }

        if (isset($refunds) && isset($refunds['eecs']) && !empty($refunds['eecs'])) {
            $_refunds = [];
            $total = 0;

            foreach ($refunds['eecs'] as $key => $eec) {
                $message = Str::replace(" (" . $eec['category'] . ")", '', Str::replace($eec['name'], '', $eec['message']));

                $__refunds = [
                    ['value' => $key + 1, 'className' => 'text__bold px-10'],
                    ['value' => "<div class='item'>
                                    {$eec['name']}.<br />
                                    <span class='f-14'>{$message}</span>
                                </div>"],
                    ['value' => $eec['category'], 'className' => 'text__center'],
                    ['value' => 1, 'className' => 'text__center'],
                ];

                if (!$this->externalEnquiry) {
                    $__refunds = [
                        ...$__refunds,
                        ['value' => FormatNumber::formatWithCurrency($eec["registration_fee"]), 'className' => 'text__center'],
                        ['value' => FormatNumber::formatWithCurrency($eec["registration_fee"]), 'className' => 'text__center text__bold']
                    ];
                }

                $_refunds[] = $__refunds;
                $total += $eec['registration_fee'];
            }

            if (!$this->externalEnquiry) {
                $_refunds[] = [
                    ['value' => 'Refunded', 'className' => 'text__bold px-10', 'attr' => 'colspan=5'],
                    ['value' => FormatNumber::formatWithCurrency($total), 'className' => 'text__center text__bold']
                ];
            }

            $data['refunds'] = $_refunds;
        }

        return new Content(
            view: 'mails.participant.entry.new',
            markdown: 'mails.participant.entry.new',
            with: [
                'title' => $this->subject,
                'member' => $this->mailHelper->topExecutiveMember(),
                'user' => [
                    'salutation_name' => $this->participantExtra?->salutation_name ?? $this->user?->salutation_name,
                ],
                // 'participants' => $participants,
                'charity' => $charity ?? null,
                'header' => $header ?? [],
                'extraData' => $this->extraData ?? [],
                'participantExtra' => $this->participantExtra,
                ...$data
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
        if ($this->invoicePdfFile) {
            return [
                Attachment::fromPath($this->invoicePdfFile)
                    ->as('invoice.pdf')
                    ->withMime('application/pdf')
            ];
        }

        return [];
    }

    private function getInvoicePdfFile()
    {
        $invoice = $this->invoice->load('upload');

        if ($invoice) {
            Log::channel('test')->error($invoice);

            if ($invoice->upload?->url) {
                $file = Storage::disk(config('filesystems.default'))->path($invoice->upload->url);

                if (!file_exists($file)) { // Regenerate the invoice pdf
                    \Log::channel('test')->debug("Regenerate Register");
                    $invoice = Invoice::generatePdf($invoice->load(['invoiceable', 'invoiceItems.invoiceItemable', 'upload']));
                    $file = Storage::disk(config('filesystems.default'))->path($invoice->upload->url);
                }
            } else { // Regenerate the invoice pdf
                \Log::channel('test')->debug("Regenerate Register 2");
                $invoice = Invoice::generatePdf($invoice->load(['invoiceable', 'invoiceItems.invoiceItemable', 'upload']));
                $file = Storage::disk(config('filesystems.default'))->path($invoice->upload->url);
            }

            return $file;
        } else {
            // Not true for LDT registrations a the invoice does not exists
            // Log::channel('adminanddeveloper')->info('Invoice Exception: Participant registration email sent without attachment - The invoice does not exists');
        }

        return null;
    }
}
