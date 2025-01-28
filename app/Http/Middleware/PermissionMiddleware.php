<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string    $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!Auth::user()->hasPermission($permission)) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to access this resource!'
            ], 403);
        }

        return $next($request);
    }
}
