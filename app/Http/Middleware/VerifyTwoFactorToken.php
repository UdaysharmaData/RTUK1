<?php

namespace App\Http\Middleware;

use App\Enums\ErrorResponseCode;
use App\Http\Resources\TwoFactorAuthMethodResource;
use Closure;
use Illuminate\Http\Request;

class VerifyTwoFactorToken
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->twoFactorAuthMethods()->exists()) {
            $token = $request->get('totp_token');

            if (!$token || !$request->user()->validTwoFactorToken($token)) {
                $methods = TwoFactorAuthMethodResource::collection($request->user()->twoFactorAuthMethods);

                return response()->json([
                    'status' => false,
                    'code' => ErrorResponseCode::InvalidTwoFactorToken,
                    'message' => 'Your two-factor token is invalid',
                    'two_factor_auth_methods' => $methods
                ], 403);
            }
        }
        return $next($request);
    }
}
