<?php

namespace App\Modules\User\Contracts;

use Illuminate\Database\Eloquent\Relations\HasOne;

interface CanReferByCode
{
    /**
     * @return HasOne
     */
    public function referralCode(): HasOne;
}
