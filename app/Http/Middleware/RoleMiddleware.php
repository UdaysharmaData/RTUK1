<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Checks if a role has permissions to a given resource
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string    $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        if (! Auth::user()->activeRole?->role?->hasPermission($permission)) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have permission to access this resource!'
            ], 403);
        }

        return $next($request);
    }
}
