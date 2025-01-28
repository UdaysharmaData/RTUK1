<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyRecaptchaMiddleware
{
    use \App\Traits\Response;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!app()->environment('local')) {

            $response = $this->sendRequest();

            if (!$response->success) {
                return $this->error('Recaptcha verification failed.', 403, $response->{'error-codes'});
            }
        }

        return $next($request); // Continue request
    }



    /**
     * Send a request to Google Recaptcha API
     *
     * @return object
     */
    private function sendRequest(): object
    {
        $client = new \GuzzleHttp\Client();

        $response = $client->request('POST', config('services.recaptcha.url'), [
            'form_params' => [
                'secret' => config('services.recaptcha.secret'),
                'response' => request('recaptcha_token'),
                'remoteip' => request()->ip(),
            ]
        ]);

        return json_decode($response->getBody());
    }
}
