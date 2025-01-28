<?php

namespace App\Modules\Event\Exceptions;

use Exception;

use App\Traits\Response;
use Illuminate\Http\JsonResponse;

class EventEventCategoryException extends Exception
{
    use Response;

    /**
     * Render the exception into an HTTP response.
     *
     * @return JsonResponse
     */
    public function render(): JsonResponse
    {
        return $this->error(
            $this->getMessage(),
            $this->getCode()
        );
    }
}
