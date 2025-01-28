<?php

namespace App\Services\TwoFactorAuth\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class EmptyPhoneNumberException extends Exception
{

    public function render(): JsonResponse
    {

        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 406);
    }
}
