<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserIsParticipant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
//        $titles = $request->user()->activeRole();
//        if ($titles->contains('developer')) return $next($request);

        abort_unless($request->user()?->isParticipant(), 403);

        return $next($request);
    }
}
