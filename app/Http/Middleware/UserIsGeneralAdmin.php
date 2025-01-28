<?php

namespace App\Http\Middleware;

use App\Http\Helpers\AccountType;
use Illuminate\Http\Request;

class UserIsGeneralAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, \Closure $next)
    {
        abort_unless(AccountType::isGeneralAdmin(),  403, 'You do not have the required level of access to perform this operation.');

        return $next($request);
    }
}
