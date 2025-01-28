<?php

namespace App\Contracts\InvoiceItemables;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface CanHaveInvoiceItemableResource
{
    /**
     * @return MorphOne
     */
    public function invoiceItem() :MorphOne;
}
