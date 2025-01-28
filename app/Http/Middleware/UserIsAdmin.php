<?php

namespace App\Http\Middleware;

use App\Traits\Response;
use Closure;
use Illuminate\Http\Request;

class UserIsAdmin
{
    use Response;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
//        $titles = $request->user()->titles();
//        if ($titles->contains('developer')) return $next($request);

        abort_unless($request->user()?->isAdmin(), 403, 'This action requires admin level access!');

        return $next($request);
    }
}
