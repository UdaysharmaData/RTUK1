<?php

namespace App\Observers;

use Exception;
use App\Modules\User\Models\VerificationCode;

class VerificationCodeObserver
{
    /**
     * Handle the VerificationCode "creating" event.
     *
     * @param VerificationCode $verificationCode
     * @return void
     * @throws Exception
     */
    public function creating(VerificationCode $verificationCode)
    {
        $verificationCode->expires_at = VerificationCode::setExpiration();
        $verificationCode->code = VerificationCode::generateCode();
    }
}
