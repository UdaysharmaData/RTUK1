<?php

namespace App\Mail\participant\entry;

use App\Modules\Setting\Enums\OrganisationEnum;
use App\Http\Helpers\FormatNumber;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use App\Enums\ParticipantPaymentStatusEnum;
use App\Modules\Setting\Enums\SiteEnum;
use App\Mail\MailLayout;
use App\Models\Invoice;
use App\Models\Upload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Modules\Participant\Models\Participant;
use App\Modules\Setting\Models\Site;

// use Illuminate\Contracts\Queue\ShouldQueue;

class ParticipantCompletedTransferMail extends MailLayout // implements ShouldQueue
{
    private Participant $oldParticipant;

    private Participant $newParticipant;

    private ?float $total;

    private $invoicePdfFile;

    public function __construct(Participant $oldParticipant, Participant $newParticipant, ?float $total, $site = null)
    {
        \Log::debug('Mail Ran');
        parent::__construct($site);

        // $this->oldParticipant = $oldParticipant->loadMissing(['eventEventCategory.event:id,ref,name,slug', 'user']);
        // $this->newParticipant = $newParticipant->loadMissing(['eventEventCategory.event:id,ref,name,slug', 'invoiceItem.invoice.upload']);
        $this->oldParticipant = $oldParticipant->load(['eventEventCategory.event:id,ref,name,slug', 'user']);
        $this->newParticipant = $newParticipant->load(['eventEventCategory.event:id,ref,name,slug', 'invoiceItem.invoice.upload']);

        $this->total = $total;

        $invoice = $this->newParticipant->invoiceItem?->invoice;

        if ($invoice && SiteEnum::belongsToOrganisation(OrganisationEnum::GWActive)) { // Invoices are not to be sent to participants for the given sites
            $this->invoicePdfFile = null;
        } else {
            // $this->invoicePdfFile = $this->getInvoicePdfFile();
        }
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
            to: $this->oldParticipant->user?->email,
            subject: 'Your Entry Has Been Transferred'
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
            view: 'mails.participant.entry.completed-transfer',
            markdown: 'mails.participant.entry.completed-transfer',
            with: [
                'total' => ($this->total && $this->total < 0) ? FormatNumber::formatWithCurrency(abs($this->total)) : null,
                'user' => [
                    'name' => $this->oldParticipant->user?->salutation_name
                ],
                'oldParticipant' => [
                    'ref' => $this->oldParticipant->ref,
                    'event' => [
                        'ref' => $this->oldParticipant->eventEventCategory->event->ref,
                        'name' => $this->oldParticipant->eventEventCategory->event->formattedName,
                        'slug' => $this->oldParticipant->eventEventCategory->event->slug,
                        'category' => $this->oldParticipant->eventEventCategory->eventCategory?->name,
                    ]
                ],
                'newParticipant' => [
                    'ref' => $this->newParticipant->ref,
                    'status' => $this->newParticipant->status,
                    'event' => [
                        'ref' => $this->newParticipant->eventEventCategory->event->ref,
                        'name' => $this->newParticipant->eventEventCategory->event->formattedName,
                        'slug' => $this->newParticipant->eventEventCategory->event->slug,
                        'category' => $this->newParticipant->eventEventCategory->eventCategory?->name,
                    ]
                ],
                'member' => $this->mailHelper->topExecutiveMember()
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
        } else {
            return [];
        }
    }

    /**
     * Set the invoice pdf file
     *
     * @return mixed
     */
    private function getInvoicePdFile(): mixed
    {
        $invoice = $this->newParticipant->invoiceItem?->invoice;

        if ($invoice) {
            if ($invoice->upload?->url) {
                $file = Storage::disk(config('filesystems.default'))->path($invoice->upload->url);

                if (!file_exists($file)) { // Regenerate the invoice pdf
                    \Log::channel('test')->debug("Regenerate Transfer");
                    $invoice = Invoice::generatePdf($invoice->load(['invoiceable', 'invoiceItems.invoiceItemable', 'upload']));
                    $file = Storage::disk(config('filesystems.default'))->path($invoice->upload->url);
                }
            } else { // Regenerate the invoice pdf
                \Log::channel('test')->debug("Regenerate Transfer 2");
                $invoice = Invoice::generatePdf($invoice->load(['invoiceable', 'invoiceItems.invoiceItemable', 'upload']));
                $file = Storage::disk(config('filesystems.default'))->path($invoice->upload->url);
            }

            return $file;
        } else if ($this->newParticipant->status != ParticipantPaymentStatusEnum::Waived) { // Not waived participants should have an invoice as they should have paid for their places
            Log::channel('adminanddeveloper')->info('Invoice Exception: Participant registration email sent without attachment - The invoice does not exists');
        }

        return null;
    }
}
