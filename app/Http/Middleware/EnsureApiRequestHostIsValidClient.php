<?php

namespace App\Http\Middleware;

use App\Services\ApiClient\ApiClientSettings;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EnsureApiRequestHostIsValidClient
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return JsonResponse|RedirectResponse|BinaryFileResponse
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next): JsonResponse|RedirectResponse|BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $apiClientRequestSettings = new ApiClientSettings($request);

        // if (\Illuminate\Support\Facades\Route::getFacadeRoot()->current()->uri() == "api/v1/payment/checkout/{type}/proceed") {
            // \Log::debug('ensure api ran');
        // }

        return $apiClientRequestSettings->clientRequestAuthorizationHandshake($next);
    }
}