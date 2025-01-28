<?php

namespace App\Modules\Finance\Models\Relations;

use App\Modules\Finance\Models\Wallet;
use App\Modules\Finance\Models\InternalTransaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait AccountRelations
{
    /**
     * Get the wallet
     *
     * @return BelongsTo
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the local transations.
     *
     * @return HasMany
     */
    public function internalTransactions(): HasMany
    {
        return $this->hasMany(InternalTransaction::class);
    }
}
