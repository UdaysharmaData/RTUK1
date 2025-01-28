<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\InvoiceItem;

class InvoiceItemObserver
{
    /**
     * Handle the InvoiceItem "created" event.
     *
     * @param  \App\Models\InvoiceItem  $invoiceItem
     * @return void
     */
    public function created(InvoiceItem $invoiceItem)
    {
        Invoice::updatePoNumberField($invoiceItem->invoice);
        Invoice::updatePriceField($invoiceItem->invoice);
    }

    /**
     * Handle the InvoiceItem "updated" event.
     *
     * @param  \App\Models\InvoiceItem  $invoiceItem
     * @return void
     */
    public function updated(InvoiceItem $invoiceItem)
    {
        Invoice::updatePriceField($invoiceItem->invoice);
    }

    /**
     * Handle the InvoiceItem "deleted" event.
     *
     * @param  \App\Models\InvoiceItem  $invoiceItem
     * @return void
     */
    public function deleted(InvoiceItem $invoiceItem)
    {
        $invoiceItem->load(["invoice" => function ($query) {
            $query->withTrashed();
        }]);

        Invoice::updatePoNumberField($invoiceItem->invoice);
        Invoice::updatePriceField($invoiceItem->invoice);

        if ($invoiceItem->invoice?->invoiceItems()->count() == 0) { // Delete the invoice if the deleted item was the only/last item
            $invoiceItem->invoice->forceDelete();
        }
    }
}
