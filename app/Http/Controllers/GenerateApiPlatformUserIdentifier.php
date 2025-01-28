<?php

namespace App\Http\Controllers;

use App\Services\ApiClient\ApiPlatformUserIdentifierGenerator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use App\Traits\Response;

final class GenerateApiPlatformUserIdentifier extends Controller
{
    use Response;

    /**
     * Generate Identifier
     *
     * Generate Unique Identifier for Request API Request User. The returned value will be used to set X-Platform-User-Identifier-Key on header.
     *
     * @group Identifier
     * @unauthenticated
     * @header Content-Type application/json
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        try {
            if (! is_null($identifier = ApiPlatformUserIdentifierGenerator::generate())) {
                return $this->success('Identifier generated.', 200, [
                    'identifier' => $identifier
                ]);
            }

            Log::error($message = 'Unable to generate valid Identifier.');
            return $this->error($message, 400);
        } catch (\Exception $exception) {
            Log::error($message = 'Unable to generate valid Identifier.');
            return $this->error($message, 400);
        }
    }
}
