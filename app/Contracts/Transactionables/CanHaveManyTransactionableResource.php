<?php

namespace App\Contracts\Transactionables;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface CanHaveManyTransactionableResource
{
    /**
     * @return MorphMany
     */
    public function transactions(): MorphMany;
}
