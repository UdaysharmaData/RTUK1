<?php

namespace App\Services\PasswordProtectionPolicy\Middleware;

use App\Services\PasswordProtectionPolicy\Contracts\KeepPasswordHistory;
use Illuminate\Http\Request;

class MaximumPasswordAgePolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|null
     */
    public function handle($request, \Closure $next)
    {
        abort_unless($request->user() && $request->user() instanceof KeepPasswordHistory, 403);

        if ($request->user()->currentPasswordHasExpired()) {
            return abort(403, 'Expired password! Please update your password to continue.');
        }

        return $next($request);
    }
}
