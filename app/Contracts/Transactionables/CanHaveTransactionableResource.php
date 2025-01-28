<?php

namespace App\Contracts\Transactionables;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface CanHaveTransactionableResource
{
    /**
     * @return MorphOne
     */
    public function transaction() :MorphOne;
}
