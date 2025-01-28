<?php

namespace App\Modules\Finance\Models\Relations;

use App\Modules\Finance\Models\Account;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait WalletRelations
{
    /**
     * @return MorphTo
     */
    public function walletable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the accounts
     *
     * @return HasMany
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
