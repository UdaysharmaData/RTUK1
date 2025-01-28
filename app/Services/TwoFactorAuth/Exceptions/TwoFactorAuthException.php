<?php

namespace App\Services\TwoFactorAuth\Exceptions;

use Illuminate\Http\JsonResponse;

class TwoFactorAuthException extends \Exception
{

    public function render(): JsonResponse
    {

        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], $this->getCode());
    }
}
