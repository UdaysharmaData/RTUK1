<?php

namespace App\Modules\Finance\Models\Relations;

use App\Traits\BelongsToSite;
use App\Modules\User\Models\User;
use App\Modules\Finance\Models\InternalTransaction;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Finance\Models\ExternalTransaction;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Finance\Models\OngoingExternalTransaction;

trait TransactionRelations
{
    use BelongsToSite;

    /**
     * @return MorphTo
     */
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
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

    /**
     * Get the ongoing external transaction
     *
     * @return BelongsTo
     */
    public function ongoingExternalTransaction(): BelongsTo
    {
        return $this->belongsTo(OngoingExternalTransaction::class);
    }

    /**
     * Get the external transaction
     *
     * @return HasOne
     */
    public function externalTransaction(): HasOne
    {
        return $this->hasOne(externalTransaction::class);
    }

    /**
     * Get the internal transactions
     *
     * @return HasMany
     */
    public function internalTransactions(): HasMany
    {
        return $this->hasMany(InternalTransaction::class);
    }
}
