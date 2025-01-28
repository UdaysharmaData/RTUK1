<?php

namespace App\Services\PasswordProtectionPolicy\Middleware;

use App\Services\PasswordProtectionPolicy\Contracts\KeepPasswordHistory;
use App\Services\PasswordProtectionPolicy\Requests\StorePassphraseRequest;
use Illuminate\Http\Request;

class RequirePassphrasePolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle($request, \Closure $next)
    {
        $this->gateCheck($request);

        if ($this->passphraseConfirmationFails($request)) {
            return response()->json([
                'message' => 'Passphrase confirmation required.',
            ], 423);
        }

        return $next($request);
    }

    /**
     * check if provided passphrase is valid
     * @param Request $request
     * @return bool
     */
    private function passphraseConfirmationFails(Request $request): bool
    {
        return ! $request->user()->verifyPassphrase();
    }

    /**
     * @param Request $request
     * @return void
     */
    protected function gateCheck(Request $request): void
    {
        $user = $request->user();
        abort_unless(isset($user) && $user instanceof KeepPasswordHistory, 403);
    }
}
