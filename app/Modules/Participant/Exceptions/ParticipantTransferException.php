<?php

namespace App\Modules\Participant\Exceptions;

use Exception;

use App\Traits\Response;
use Illuminate\Http\JsonResponse;

class ParticipantTransferException extends Exception
{
    use Response;

    public $errorData;

    public function __construct($message = "", $code = 406, $errorData = null, Exception $previous = null)
    {
        $this->errorData = $errorData;
        
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @return JsonResponse
     */
    public function render(): JsonResponse
    {
        return $this->error(
            $this->getMessage(),
            $this->getCode(),
            $this->errorData
        );
    }
}
