<?php

namespace App\Contracts\Walletables;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManyWalletableResource
{
    /**
     * @return MorphMany
     */
    public function wallets(): MorphMany;
}
