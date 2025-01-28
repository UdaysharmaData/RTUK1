<?php

namespace App\Http\Middleware;

use Closure;
use App\Traits\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Server\Exception\OAuthServerException;

class ClientAuthenticationMiddleware
{
    use Response;

    /**
     * Handle client requests that works with and without authentication.
     *
     * @param  Request   $request
     * @param  Closure  $next
     * @param  string    $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if  ($request->hasHeader('Authorization')) {
            try {
                if (!auth('api')->check()) {
                    return $this->error('Unauthorized', 401);
                }
            } catch (OAuthServerException|\Laravel\Passport\Exceptions\OAuthServerException $exception) {
                Log::error($exception);
                return $this->error('Invalid Authorization token', 400);
            } catch (\Exception $e) {
                Log::error($e);
                return $this->error('An error occurred while authenticating client.', 500);
            }
        }

        return $next($request);
    }
}
