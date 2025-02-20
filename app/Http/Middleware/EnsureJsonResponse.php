<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
//        $response = $next($request);
//        $response->headers->set('Access-Control-Allow-Origin' , '*');
//        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE');
//        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With, Application, Location');
//
//        return $response;

        return $next($request);
    }
}
