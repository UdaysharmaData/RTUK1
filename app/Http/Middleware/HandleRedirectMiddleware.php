<?php

namespace App\Http\Middleware;

use App\Enums\RedirectStatusEnum;
use App\Models\Redirect;
use App\Traits\Response;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HandleRedirectMiddleware
{
    use Response;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure(Request): (\Illuminate\Http\Response|RedirectResponse) $next
     * @return JsonResponse|\Illuminate\Http\Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next): \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
//        $redirect = Redirect::query()
//            ->where('target_url', '=', $url = $request->fullUrl())
//            ->where('is_active', '=', 1)
//            ->first();
//
//        if ($request->isMethod('GET') && ! is_null($redirect)) {
//            return response()->json([
//                'status' => false,
//                'message' => 'Resource not found',
//            ], $redirect->status->value === RedirectStatusEnum::Permanent ? 301 : 302, [
//                'Location' => $redirect->redirect_url
//            ]);
//        }

        return $next($request);
    }
}
