<?php

namespace App\Jobs;

use App\Enums\QueueNameEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

use App\Models\Invoice;

class GenerateInvoicePdfJob //implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invoice;
    public $regenerate;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * Create a new job instance.
     * 
     * @param  Invoice  $invoice
     * @param  bool     $regenerate
     * @return void
     */
    public function __construct(Invoice $invoice, bool $regenerate = false)
    {
        // $this->onConnection(QueueNameEnum::High->value); // Set the connection name

        $this->invoice = $invoice->load(['invoiceable', 'invoiceItems.invoiceItemable', 'upload']);
        $this->regenerate = $regenerate;
        // $this->queue = QueueNameEnum::High->value;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        \Log::channel('test')->debug('GenerateInvoicePdfJob Ran');

        Invoice::generatePdf($this->invoice, $this->regenerate);
    }
}
