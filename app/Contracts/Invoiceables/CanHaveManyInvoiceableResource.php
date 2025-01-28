<?php

namespace App\Contracts\Invoiceables;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManyInvoiceableResource
{
    /**
     * @return MorphMany
     */
    public function invoices(): MorphMany;
}
