<?php

namespace App\Contracts\Walletables;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface CanHaveWalletableResource
{
    /**
     * @return MorphOne
     */
    public function wallet() :MorphOne;
}
