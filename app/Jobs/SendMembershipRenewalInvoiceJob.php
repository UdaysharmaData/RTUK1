<?php

namespace App\Jobs;

use App\Mail\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

use App\Models\Invoice;
use App\Modules\Charity\Models\Charity;
use App\Mail\SendMembershipRenewalInvoiceMail;

class SendMembershipRenewalInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $charity;
    public $invoice;

    /**
     * Create a new job instance.
     * @param  Charity  $charity
     * @param  Invoice  $invoice
     * @return void
     */
    public function __construct(Charity $charity, Invoice $invoice)
    {
        $this->charity = $charity;
        $this->invoice = $invoice;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->invoice->load(['invoiceable', 'invoiceItems.invoiceItemable', 'upload']);
        $this->charity->load('charityManager.user');

        $this->invoice = Invoice::generatePdf($this->invoice);

        // Send invoice to charity
        Mail::site()->to($this->charity->finance_contact_email ?? $this->charity->email)->queue(new SendMembershipRenewalInvoiceMail('Membership Renewal', $this->charity, $this->invoice));
    }
}
