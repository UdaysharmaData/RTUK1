<?php

namespace App\Models\Relations;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait InvoiceItemRelations
{
    /**
     * @return BelongsTo
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    /**
     * @return MorphTo
     */
    public function invoiceItemable(): MorphTo
    {
        return $this->morphTo();
    }
}