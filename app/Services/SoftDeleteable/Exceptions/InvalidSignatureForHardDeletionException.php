<?php

namespace App\Services\SoftDeleteable\Exceptions;

use Throwable;

class InvalidSignatureForHardDeletionException extends \Exception
{
    public function __construct(string $message = "Action verification failed.", int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
