<?php

namespace App\Contracts\InvoiceItemables;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManyInvoiceItemableResource
{
    /**
     * @return MorphMany
     */
    public function invoiceItems(): MorphMany;
}
