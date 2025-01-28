<?php

namespace App\Services\SoftDeleteable\Exceptions;

use Throwable;

class DeletionConfirmationRequiredException extends \Exception
{
    /**
     * @var array
     */
    public array $payload;

    public function __construct(string $message = "Confirmation required to complete action.", int $code = 400, ?Throwable $previous = null, array $payload = [])
    {
        parent::__construct($message, $code, $previous);

        $this->payload = $payload;
    }
}
