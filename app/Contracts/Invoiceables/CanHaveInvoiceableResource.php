<?php

namespace App\Contracts\Invoiceables;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface CanHaveInvoiceableResource
{
    /**
     * @return MorphOne
     */
    public function invoice() :MorphOne;
}
