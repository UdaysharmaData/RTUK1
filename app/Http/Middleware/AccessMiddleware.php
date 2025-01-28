<?php

namespace App\Http\Middleware;

use Auth;
use Flash;
use Closure;
use Illuminate\Http\Request;

use App\Http\Helpers\AccountType;

class AccessMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string    $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // TODO: Revise this logic given that a user (charity owner) can have multiple charities (according to the structure of the current database)
        // if (AccountType::isCharityOwner() /*&& Auth::user()->charity->membership_type == 'partner'*/) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'For full access, contact helen@runforcharity.com'
        //     ], 403);
        // }

        return $next($request);
    }
}
