<?php

namespace App\Modules\Finance\Models\Relations;

use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait InternalTransactionRelations
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
     * Get the account.
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
