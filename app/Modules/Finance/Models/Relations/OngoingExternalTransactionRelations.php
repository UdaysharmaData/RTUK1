<?php

namespace App\Modules\Finance\Models\Relations;

use App\Modules\User\Models\User;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait OngoingExternalTransactionRelations
{
    /**
     * Get the external transactions associated with the current payment_intent_id.
     *
     * @return HasMany
     */
    public function externalTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payment_intent_id', 'payment_intent_id');
    }

    /**
     * Get the transactions
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the user
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
