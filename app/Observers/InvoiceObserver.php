<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\DataCaching\CacheDataManager;
use App\Services\DataServices\InvoiceDataService;

class InvoiceObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    // public $afterCommit = true;

    /**
     * Handle the Invoice "created" event.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function created(Invoice $invoice): void
    {
        Invoice::updatePoNumberField($invoice);
        CacheDataManager::flushAllCachedServiceListings(new InvoiceDataService());
    }

    /**
     * Handle the Invoice "updated" event.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function updated(Invoice $invoice): void
    {
        CacheDataManager::flushAllCachedServiceListings(new InvoiceDataService());
    }

    /**
     * Handle the Invoice "deleted" event.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function deleted(Invoice $invoice): void
    {
        CacheDataManager::flushAllCachedServiceListings(new InvoiceDataService());
    }

    /**
     * Handle the Invoice "deleted" event.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function deleting(Invoice $invoice)
    {
        //
    }

    /**
     * Handle the Invoice "restored" event.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function restored(Invoice $invoice): void
    {
        CacheDataManager::flushAllCachedServiceListings(new InvoiceDataService());
    }

    /**
     * Handle the Invoice "force deleted" event.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function forceDeleted(Invoice $invoice): void
    {
        \Log::debug('Mesmer force deleted');

        if ($invoice->invoiceItems()->count() > 0) { // Delete the invoice items once the invoice gets force deleted
            $invoice->invoiceItems()->delete();
        }

        CacheDataManager::flushAllCachedServiceListings(new InvoiceDataService());
    }

    /**
     * Handle the Invoice "force deleting" event.
     * // TODO: This event is not been triggered. Look into it.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function forceDeleting(Invoice $invoice): void
    {
        \Log::debug('Mesmer look here force deleting seems to work!');

        if ($invoice->invoiceItems()->count() > 0) { // Delete the invoice items once the invoice gets force deleted
            $invoice->invoiceItems()->delete();
        }
    }
}
