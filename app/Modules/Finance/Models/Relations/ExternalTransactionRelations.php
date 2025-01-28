<?php

namespace App\Modules\Finance\Models\Relations;

use App\Modules\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Finance\Models\OngoingExternalTransaction;

trait ExternalTransactionRelations
{
    /**
     * Get the transaction.
     *
     * @return BelongsTo
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get the ongoing external transaction.
     *
     * @return BelongsTo
     */
    public function ongoingExternalTransaction(): BelongsTo
    {
        return $this->belongsTo(OngoingExternalTransaction::class, 'payment_intent_id', 'payment_intent_id');
    }
}
